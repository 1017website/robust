<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $selectedDocument = $documents->first();
            return view('drafter.documents.index', compact('documents', 'stats', 'selectedDocument'));
        }

        return view('shared.documents.index', compact('documents', 'stats'));
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
                abort_unless($documentable instanceof DesignRequest && $documentable->hasPrePo(), 422, 'Gambar fabrikasi baru dapat diunggah setelah Pra PO / Request PO tersedia.');
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

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function destroy(Document $document)
    {
        abort_unless($this->canManageDocument($document), 403);
        $document->delete();
        return back()->with('success', 'Dokumen berhasil diarsipkan.');
    }

    protected function documentableTypes(): array
    {
        return [
            Customer::class => Customer::class,
            DesignRequest::class => DesignRequest::class,
            Lead::class => Lead::class,
            Project::class => Project::class,
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
                $documentable instanceof Project => (int) $documentable->project_manager_id === (int) $user->id
                    || (int) ($documentable->quotation?->sales_id) === (int) $user->id,
                default => false,
            };
        }

        if ($user->isDrafter()) {
            return $documentable instanceof DesignRequest
                && (int) $documentable->production_pic_id === (int) $user->id;
        }

        if ($user->isProduction()) {
            return $documentable instanceof DesignRequest;
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
}
