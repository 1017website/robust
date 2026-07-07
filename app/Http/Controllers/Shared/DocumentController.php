<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DesignRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('uploader', 'documentable')->latest();
        if (Auth::user()->isDrafter()) {
            $query->where(function ($q) {
                $q->where('uploaded_by', Auth::id())
                    ->orWhere('documentable_type', DesignRequest::class)
                    ->orWhere('documentable_type', 'App\\Models\\DesignRequest');
            });
        }
        if ($cat = $request->get('category')) {
            $query->where('category', $cat);
        }
        if ($s = $request->get('q')) {
            $query->where('name', 'like', "%$s%");
        }
        $documents = $query->paginate(10)->withQueryString();

        $statsQuery = Document::query();
        if (Auth::user()->isDrafter()) {
            $statsQuery->where(function ($q) {
                $q->where('uploaded_by', Auth::id())
                    ->orWhere('documentable_type', DesignRequest::class)
                    ->orWhere('documentable_type', 'App\\Models\\DesignRequest');
            });
        }

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
            'documentable_type' => ['required', 'string'],
            'documentable_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $path = $request->file('file')->store('documents', 'public');

        Document::create([
            'documentable_type' => $data['documentable_type'],
            'documentable_id' => $data['documentable_id'],
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
        $document->delete();
        return back()->with('success', 'Dokumen dihapus.');
    }
}
