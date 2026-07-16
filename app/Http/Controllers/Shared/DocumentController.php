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
        ]);

        $documentableClass = $this->documentableTypes()[$data['documentable_type']];
        $documentable = $documentableClass::findOrFail($data['documentable_id']);
        abort_unless($this->canAccessDocumentable($documentable), 403);

        $path = $request->file('file')->store('documents', 'public');

        Document::create([
            'documentable_type' => $documentableClass,
            'documentable_id' => $documentable->id,
            'name' => $data['name'],
            'category' => $data['category'] ?? 'lainnya',
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_type' => $request->file('file')->getClientOriginalExtension(),
            'file_size' => $request->file('file')->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

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

        return false;
    }

    protected function canManageDocument(Document $document): bool
    {
        $user = Auth::user();

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
