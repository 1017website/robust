<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Services\SpreadsheetPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('uploader', 'documentable')->visibleTo(Auth::user())->latest();
        if ($cat = $request->get('category')) {
            $query->where('category', $cat);
        }
        if ($s = $request->get('q')) {
            $query->where('name', 'like', "%$s%");
        }
        if ($request->filled('project')) {
            $query->where('documentable_type', Project::class)
                ->where('documentable_id', $request->integer('project'));
        }
        $documents = $query->paginate(10)->withQueryString();

        $statsQuery = Document::query()->visibleTo(Auth::user());

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'drawing' => (clone $statsQuery)->where('category', 'drawing')->count(),
            'boq' => (clone $statsQuery)->where('category', 'boq')->count(),
            'laporan' => (clone $statsQuery)->where('category', 'laporan')->count(),
            'lainnya' => (clone $statsQuery)->whereNotIn('category', ['drawing', 'boq', 'laporan'])->count(),
        ];

        if (Auth::user()->isDrafter()) {
            $selectedDocument = $request->filled('document')
                ? $documents->getCollection()->firstWhere('id', (int) $request->get('document'))
                : null;
            $selectedDocument ??= $documents->first();
            return view('drafter.documents.index', compact('documents', 'stats', 'selectedDocument'));
        }

        $projectOptions = Auth::user()->isProduction()
            ? Project::query()
                ->whereHas('documents', fn ($documents) => $documents->where('category', 'fabrication_drawing')->where('is_current', true))
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
            : collect();

        return view('shared.documents.index', compact('documents', 'stats', 'projectOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'documentable_type' => ['required', 'string', Rule::in(array_keys($this->documentableTypes()))],
            'documentable_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
            'replaces_document_id' => ['nullable', 'exists:documents,id'],
            'revision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $documentableClass = $this->documentableTypes()[$data['documentable_type']];
        $documentable = $documentableClass::findOrFail($data['documentable_id']);
        abort_unless($this->canAccessDocumentable($documentable), 403);

        $user = Auth::user();
        abort_if($user->isProduction(), 403, 'Role Produksi tidak memiliki akses upload drawing/dokumen.');
        if ($user->isDrafter()) {
            abort_unless(in_array($data['category'] ?? null, ['request_drawing', 'fabrication_drawing', 'supporting_document'], true), 422, 'Kategori dokumen drafter tidak valid.');
            if (($data['category'] ?? null) === 'fabrication_drawing') {
                $canUploadFabrication = $documentable instanceof DesignRequest
                    ? $documentable->hasPrePo()
                    : ($documentable instanceof Project
                        && in_array($documentable->quotation?->purchaseOrderRequest?->status, [
                            'po_created', 'production', 'installation', 'invoicing', 'paid',
                        ], true));
                abort_unless($canUploadFabrication, 422, 'Gambar fabrikasi baru dapat diunggah setelah PO Accurate dibuat.');
            }
        }

        $replaced = null;
        $revisionNumber = 1;
        $parentDocumentId = null;
        if (! empty($data['replaces_document_id'])) {
            $replaced = Document::findOrFail($data['replaces_document_id']);
            abort_unless($this->canManageDocument($replaced), 403);
            abort_unless($replaced->documentable_type === $documentableClass && (int) $replaced->documentable_id === (int) $documentable->id, 422, 'Dokumen revisi tidak sesuai Design Request.');
            $parentDocumentId = $replaced->parent_document_id ?: $replaced->id;
            $revisionNumber = Document::where(fn ($query) => $query->where('id', $parentDocumentId)->orWhere('parent_document_id', $parentDocumentId))->max('revision_number') + 1;
        }

        $path = $request->file('file')->store('documents', 'public');

        $document = Document::create([
            'parent_document_id' => $parentDocumentId,
            'documentable_type' => $documentableClass,
            'documentable_id' => $documentable->id,
            'name' => $data['name'],
            'category' => $data['category'] ?? 'lainnya',
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_type' => $request->file('file')->getClientOriginalExtension(),
            'file_size' => $request->file('file')->getSize(),
            'version' => 'v'.$revisionNumber.'.0',
            'revision_number' => $revisionNumber,
            'is_current' => true,
            'revision_note' => $data['revision_note'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        if ($replaced) {
            Document::where(fn ($query) => $query->where('id', $parentDocumentId)->orWhere('parent_document_id', $parentDocumentId))
                ->where('id', '!=', $document->id)->update(['is_current' => false]);
        }

        if ($documentable instanceof DesignRequest && $user->isDrafter()) {
            $openRevision = $documentable->revisionRequests()
                ->where('status', 'requested')
                ->latest('revision_number')
                ->first();
            $hasRevisionHistory = $documentable->revisionRequests()->exists();

            if ($openRevision && $documentable->hasProductionReadyDocument($openRevision->requested_at)) {
                $openRevision->update([
                    'status' => 'drawing_uploaded',
                    'drawing_uploaded_at' => now(),
                ]);
                $documentable->update([
                    'status' => 'revision_drawing_uploaded',
                    'progress' => max(25, (int) $documentable->progress),
                ]);
            } elseif (! $hasRevisionHistory && $documentable->hasProductionReadyDocument()) {
                $documentable->update([
                    'status' => 'drawing_uploaded',
                    'progress' => max(25, (int) $documentable->progress),
                ]);
            }
        }

        if ($documentable instanceof Project && $user->isDrafter() && $document->category === 'fabrication_drawing') {
            $documentable->workflow()->firstOrCreate();
            $documentable->update([
                'status' => 'ongoing',
                'progress' => max(10, (int) $documentable->progress),
            ]);
        }

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function destroy(Document $document)
    {
        abort_if(
            $document->documentable_type === DesignRequest::class,
            422,
            'Drawing dan dokumen Design Request tidak dapat dihapus. Unggah file baru sebagai revisi.'
        );
        abort_unless($this->canManageDocument($document), 403);
        $document->delete();
        return back()->with('success', 'Dokumen berhasil diarsipkan.');
    }

    public function download(Document $document)
    {
        $this->authorizeDocumentView($document);
        abort_unless(
            $document->file_path && Storage::disk('public')->exists($document->file_path),
            404,
            'File dokumen tidak ditemukan.'
        );

        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION)
            ?: ltrim((string) $document->file_type, '.');
        $baseName = trim(str_replace(['/', '\\'], '-', $document->name))
            ?: 'dokumen-'.$document->getKey();
        $filename = $extension && ! str_ends_with(strtolower($baseName), '.'.strtolower($extension))
            ? $baseName.'.'.$extension
            : $baseName;

        return Storage::disk('public')->download($document->file_path, $filename);
    }

    public function preview(Document $document, SpreadsheetPreview $spreadsheetPreview)
    {
        $this->authorizeDocumentView($document);
        abort_unless(
            $document->file_path && Storage::disk('public')->exists($document->file_path),
            404,
            'File dokumen tidak ditemukan.'
        );

        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION) ?: (string) $document->file_type);
        $absolutePath = Storage::disk('public')->path($document->file_path);

        if (in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            return response()->file($absolutePath, [
                'Content-Type' => Storage::disk('public')->mimeType($document->file_path),
                'Content-Disposition' => 'inline; filename="'.basename($absolutePath).'"',
            ]);
        }

        $preview = null;
        $previewError = null;
        try {
            $preview = $spreadsheetPreview->rows($absolutePath);
        } catch (\RuntimeException $exception) {
            $previewError = $exception->getMessage();
        }

        return view('shared.documents.preview', compact('document', 'preview', 'previewError'));
    }

    protected function documentableTypes(): array
    {
        return [
            Customer::class => Customer::class,
            DesignRequest::class => DesignRequest::class,
            Lead::class => Lead::class,
            Project::class => Project::class,
            Quotation::class => Quotation::class,
        ];
    }

    protected function canAccessDocumentable($documentable): bool
    {
        $user = Auth::user();

        if ($user->isAdminLevel() || $user->isSalesSpv()) {
            return true;
        }

        if ($user->isSales()) {
            return match (true) {
                $documentable instanceof Customer => (int) $documentable->sales_id === (int) $user->id,
                $documentable instanceof DesignRequest => (int) $documentable->sales_id === (int) $user->id,
                $documentable instanceof Lead => (int) $documentable->sales_id === (int) $user->id,
                $documentable instanceof Quotation => (int) $documentable->sales_id === (int) $user->id,
                $documentable instanceof Project => (int) $documentable->project_manager_id === (int) $user->id
                    || (int) ($documentable->quotation?->sales_id) === (int) $user->id,
                default => false,
            };
        }

        if ($user->isDrafter()) {
            return match (true) {
                $documentable instanceof DesignRequest => (int) $documentable->production_pic_id === (int) $user->id,
                $documentable instanceof Project => \App\Support\ProjectAccess::canView($user, $documentable),
                default => false,
            };
        }

        if ($user->isProduction()) {
            return $documentable instanceof Project
                && \App\Support\ProjectAccess::canView($user, $documentable);
        }

        return false;
    }

    protected function canManageDocument(Document $document): bool
    {
        $user = Auth::user();

        if ($user->isProduction()) {
            return false;
        }

        if ($user->isAdminLevel()) {
            return true;
        }

        if ((int) $document->uploaded_by === (int) $user->id) {
            return true;
        }

        $document->loadMissing('documentable');

        return $document->documentable && $this->canAccessDocumentable($document->documentable);
    }

    protected function authorizeDocumentView(Document $document): void
    {
        $user = Auth::user();
        $visible = $user->isDrafter()
            || Document::query()->visibleTo($user)->whereKey($document->getKey())->exists();

        // File penawaran utama dapat memuat harga. Role monitoring (termasuk
        // SPV, produksi, QC, delivery, dan administrasi) hanya melihat metadata.
        if ($document->category === 'quotation_file' && ! $user->canViewPrices()) {
            $visible = false;
        }

        // SPV hanya memonitor siapa yang membuat penawaran. Lampiran tetap
        // ditampilkan sebagai metadata karena isinya dapat memuat nominal.
        if ($user->isSalesSpv() && $document->documentable_type === Quotation::class) {
            $visible = false;
        }

        abort_unless($visible, 403, 'Anda tidak memiliki akses ke dokumen ini.');
    }
}
