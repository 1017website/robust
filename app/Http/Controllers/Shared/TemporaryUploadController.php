<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\TemporaryUpload;
use Illuminate\Http\Request;

class TemporaryUploadController extends Controller
{
    public function store(Request $request, TemporaryUpload $uploads)
    {
        $request->validate(['file' => ['required', 'file']]);

        return response()->json([
            'token' => $uploads->store($request->file('file'), $request->user()->id),
        ], 201);
    }

    public function destroy(Request $request, string $token, TemporaryUpload $uploads)
    {
        $uploads->delete($token, $request->user()->id);

        return response()->noContent();
    }
}
