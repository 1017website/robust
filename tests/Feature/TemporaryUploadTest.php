<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemporaryUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $user = new User(['name' => 'Upload Tester', 'role' => 'sales']);
        $user->id = 71;
        $this->actingAs($user);

        Route::middleware(['web', 'auth'])->post('/test-upload-save', function (Request $request) {
            $data = $request->validate([
                'title' => ['required', 'string'],
                'items.2.quotation_image' => ['required', 'image', 'mimes:png'],
                'documents' => ['required', 'array'],
                'documents.*' => ['file', 'mimes:pdf'],
            ]);

            return response()->json([
                'image' => $data['items'][2]['quotation_image']->store('images', 'public'),
                'documents' => array_map(fn ($file) => [
                    'path' => $file->store('documents', 'public'),
                    'name' => $file->getClientOriginalName(),
                ], $data['documents']),
            ]);
        });
    }

    private function stage(UploadedFile $file): string
    {
        return $this->postJson(route('temporary-uploads.store'), ['file' => $file])
            ->assertCreated()->json('token');
    }

    private function fields(): array
    {
        return [
            ['field' => 'items.2.quotation_image', 'token' => $this->stage(UploadedFile::fake()->image('gambar.png'))],
            ['field' => 'documents.0', 'token' => $this->stage(UploadedFile::fake()->createWithContent('satu.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"))],
            ['field' => 'documents.1', 'token' => $this->stage(UploadedFile::fake()->createWithContent('dua.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"))],
        ];
    }

    public function test_save_uses_staged_nested_and_multiple_files_without_reuploading(): void
    {
        $fields = $this->fields();
        $response = $this->postJson('/test-upload-save', ['title' => 'Gambar', '_uploaded_files' => $fields])->assertOk();
        Storage::disk('public')->assertExists($response->json('image'));
        Storage::disk('public')->assertExists($response->json('documents.0.path'));
        $response->assertJsonPath('documents.1.name', 'dua.pdf');
        $this->assertSame([], Storage::disk('local')->allFiles('temporary-uploads'));
    }

    public function test_validation_failure_preserves_uploads_for_retry(): void
    {
        $fields = $this->fields();
        $this->postJson('/test-upload-save', ['_uploaded_files' => $fields])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->postJson('/test-upload-save', ['title' => 'Diperbaiki', '_uploaded_files' => $fields])->assertOk();
    }

    public function test_existing_module_file_rules_still_reject_wrong_types(): void
    {
        $fields = $this->fields();
        $fields[0]['token'] = $this->stage(UploadedFile::fake()->create('bukan-gambar.pdf', 4, 'application/pdf'));
        $this->postJson('/test-upload-save', ['title' => 'Gambar', '_uploaded_files' => $fields])
            ->assertUnprocessable()->assertJsonValidationErrors('items.2.quotation_image');
    }

    public function test_normal_form_validation_keeps_tokens_and_names_in_old_input(): void
    {
        $fields = $this->fields();
        $this->from('/test-form')->post('/test-upload-save', ['_uploaded_files' => $fields])
            ->assertRedirect('/test-form')->assertSessionHasErrors('title')
            ->assertSessionHas('_old_input._uploaded_files.1.name', 'satu.pdf');
        $this->post('/test-upload-save', ['title' => 'Diperbaiki', '_uploaded_files' => $fields])->assertOk();
        $this->assertSame([], Storage::disk('local')->allFiles('temporary-uploads'));
    }

    public function test_other_users_cannot_use_or_delete_uploaded_tokens(): void
    {
        $fields = $this->fields();
        $other = new User(['name' => 'Other', 'role' => 'sales']);
        $other->id = 72;
        $this->actingAs($other)->deleteJson(route('temporary-uploads.destroy', $fields[0]['token']))->assertNoContent();
        Storage::disk('local')->assertExists('temporary-uploads/71/'.$fields[0]['token'].'/file');
        $this->postJson('/test-upload-save', ['title' => 'Gambar', '_uploaded_files' => $fields])->assertUnprocessable();
    }

    public function test_expired_files_are_rejected_and_pruned(): void
    {
        $fields = $this->fields();
        $this->travel(25)->hours();
        $this->postJson('/test-upload-save', ['title' => 'Gambar', '_uploaded_files' => $fields])->assertUnprocessable();
        $this->artisan('uploads:prune')->assertSuccessful();
        $this->assertSame([], Storage::disk('local')->allFiles('temporary-uploads'));
    }

    public function test_upload_requires_authentication_and_supports_removal(): void
    {
        $token = $this->stage(UploadedFile::fake()->create('dokumen.pdf', 1, 'application/pdf'));
        $this->deleteJson(route('temporary-uploads.destroy', $token))->assertNoContent();
        Storage::disk('local')->assertMissing("temporary-uploads/71/{$token}/file");
        auth()->forgetGuards();
        $this->postJson(route('temporary-uploads.store'), ['file' => UploadedFile::fake()->create('test.pdf')])->assertUnauthorized();
    }
}
