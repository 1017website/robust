<?php

namespace App\Http\Middleware;

use App\Services\TemporaryUpload;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ResolveTemporaryUploads
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->has('_uploaded_files')) {
            return $next($request);
        }

        abort_unless($request->user(), 401);
        $data = $request->validate([
            '_uploaded_files' => ['required', 'array', 'max:1000'],
            '_uploaded_files.*.field' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z0-9_]+)*$/'],
            '_uploaded_files.*.token' => ['required', 'uuid'],
        ]);

        $uploads = app(TemporaryUpload::class);
        $files = $request->allFiles();
        $staged = [];
        foreach ($data['_uploaded_files'] as $upload) {
            $file = $uploads->resolve($upload['token'], $request->user()->id, $upload['field']);
            Arr::set($files, $upload['field'], $file);
            $staged[] = $upload + ['name' => $file->getClientOriginalName()];
        }
        // allFiles() sudah di-cache oleh middleware sebelumnya; reset setelah memasang file.
        (function (array $files) {
            $this->files->replace($files);
            $this->convertedFiles = null;
        })->call($request, $files);
        // Token ikut old input saat validasi gagal, agar file tidak perlu diunggah ulang.
        $request->merge(['_uploaded_files' => $staged]);

        $previousErrors = $request->session()->get('errors');
        $response = $next($request);
        if ($response->getStatusCode() < 400 && $request->session()->get('errors') === $previousErrors) {
            foreach ($data['_uploaded_files'] as $upload) {
                $uploads->delete($upload['token'], $request->user()->id);
            }
        }

        return $response;
    }
}
