<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('uploader')->latest();
        if ($cat = $request->get('category')) {
            $query->where('category', $cat);
        }
        if ($s = $request->get('q')) {
            $query->where('name', 'like', "%$s%");
        }
        $documents = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => Document::count(),
            'drawing' => Document::where('category', 'drawing')->count(),
            'boq' => Document::where('category', 'boq')->count(),
            'laporan' => Document::where('category', 'laporan')->count(),
            'lainnya' => Document::whereNotIn('category', ['drawing', 'boq', 'laporan'])->count(),
        ];

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
