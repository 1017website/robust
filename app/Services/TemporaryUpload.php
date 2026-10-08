<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TemporaryUpload
{
    public function store(UploadedFile $file, int $userId): string
    {
        $token = (string) Str::uuid();
        $directory = "temporary-uploads/{$userId}/{$token}";
        $disk = Storage::disk('local');

        if (! $disk->putFileAs($directory, $file, 'file') || ! $disk->put("{$directory}/metadata.json", json_encode([
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'expires_at' => now()->addDay()->timestamp,
        ], JSON_THROW_ON_ERROR))) {
            $disk->deleteDirectory($directory);
            abort(500, 'File gagal diunggah. Silakan coba lagi.');
        }

        return $token;
    }

    public function resolve(string $token, int $userId, string $field): UploadedFile
    {
        $directory = "temporary-uploads/{$userId}/{$token}";
        $disk = Storage::disk('local');
        $metadata = Str::isUuid($token) && $disk->exists("{$directory}/metadata.json")
            ? json_decode($disk->get("{$directory}/metadata.json"), true)
            : null;

        if (! $metadata || $metadata['expires_at'] <= now()->timestamp || ! $disk->exists("{$directory}/file")) {
            throw ValidationException::withMessages([$field => 'Upload tidak ditemukan atau sudah kedaluwarsa. Pilih ulang file.']);
        }

        // File sudah berada di server, sehingga bukan lagi upload HTTP PHP.
        return new UploadedFile($disk->path("{$directory}/file"), $metadata['name'], $metadata['mime'], null, true);
    }

    public function delete(string $token, int $userId): void
    {
        if (Str::isUuid($token)) {
            Storage::disk('local')->deleteDirectory("temporary-uploads/{$userId}/{$token}");
        }
    }

    public function prune(): int
    {
        $disk = Storage::disk('local');
        $count = 0;
        foreach ($disk->directories('temporary-uploads') as $userDirectory) {
            foreach ($disk->directories($userDirectory) as $directory) {
                $path = "{$directory}/metadata.json";
                $metadata = $disk->exists($path) ? json_decode($disk->get($path), true) : null;
                $expired = $metadata
                    ? $metadata['expires_at'] <= now()->timestamp
                    : ($disk->exists("{$directory}/file") && $disk->lastModified("{$directory}/file") <= now()->subDay()->timestamp);
                if ($expired) {
                    $disk->deleteDirectory($directory);
                    $count++;
                }
            }
        }

        return $count;
    }
}
