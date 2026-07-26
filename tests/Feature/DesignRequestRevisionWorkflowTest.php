<?php

namespace Tests\Feature;

use App\Models\DesignRequest;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesignRequestRevisionWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_design_request_role_gates_and_revision_cycle_are_enforced(): void
    {
        Storage::fake('public');

        $sales = User::factory()->create(['role' => 'sales']);
        $drafter = User::factory()->create(['role' => 'drafter']);
        $production = User::factory()->create(['role' => 'production']);

        $designRequest = DesignRequest::create([
            'code' => 'DR-REV-'.str()->upper(str()->random(6)),
            'customer_name' => 'Customer Revision Flow',
            'pic_name' => 'PIC Revision',
            'project_name' => 'Project Revision Flow',
            'sales_id' => $sales->id,
            'production_pic_id' => $drafter->id,
            'request_date' => today(),
            'deadline' => today()->addWeek(),
            'priority' => 'normal',
            'status' => 'assigned',
            'progress' => 0,
        ]);

        $this->actingAs($drafter)
            ->get(route('drafter.design-requests.index'))
            ->assertOk()
            ->assertSee($designRequest->code)
            ->assertSee('Riwayat')
            ->assertSee('Revisi')
            ->assertSee('Dokumen');

        $this->actingAs($production)
            ->get(route('drafter.design-requests.index'))
            ->assertOk()
            ->assertSee($designRequest->code);

        $this->actingAs($drafter)
            ->post(route('drafter.design-requests.feedback', $designRequest), ['action' => 'save'])
            ->assertForbidden();

        $this->actingAs($production)
            ->post(route('drafter.design-requests.feedback', $designRequest), ['action' => 'save'])
            ->assertSessionHasErrors('action');

        $this->actingAs($drafter)
            ->post(route('documents.store'), [
                'documentable_type' => DesignRequest::class,
                'documentable_id' => $designRequest->id,
                'name' => 'Drawing Awal',
                'category' => 'request_drawing',
                'file' => UploadedFile::fake()->create('drawing-awal.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect();

        $initialDocument = Document::where('documentable_type', DesignRequest::class)
            ->where('documentable_id', $designRequest->id)
            ->where('name', 'Drawing Awal')
            ->firstOrFail();

        $this->assertSame('Dokumen awal', $initialDocument->revisionLabel());
        $this->assertSame('drawing_uploaded', $designRequest->fresh()->status);

        $readyForProductionCount = DesignRequest::whereIn('status', [
            'drawing_uploaded',
            'revision_drawing_uploaded',
        ])->count();

        $this->actingAs($production)
            ->get(route('drafter.design-requests.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'bi bi-pencil-square',
                'Design Request',
                'side-badge',
                (string) min(99, $readyForProductionCount),
            ], false);

        $this->actingAs($production)
            ->get(route('drafter.design-requests.show', $designRequest))
            ->assertOk()
            ->assertDontSeeText('Dimensi Utama')
            ->assertDontSee('name="dimensions', false);

        $this->actingAs($production)
            ->post(route('drafter.design-requests.feedback', $designRequest), [
                'cost_material' => 1000000,
                'cost_production' => 500000,
                'cost_installation' => 250000,
                'technical_note' => 'Spesifikasi awal.',
                'items' => [[
                    'name' => 'Meja Lab',
                    'qty' => 1,
                    'unit' => 'Unit',
                    'unit_price' => 1750000,
                ]],
                'action' => 'submit',
            ])
            ->assertRedirect(route('drafter.design-requests.index'));

        $this->assertSame('completed', $designRequest->fresh()->status);
        $this->assertSame(1750000.0, (float) $designRequest->fresh()->cost_total);
        $this->assertCount(1, $designRequest->fresh()->items);

        $this->actingAs($sales)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Design Request selesai diproses')
            ->assertSeeInOrder([
                'bi bi-file-earmark-text',
                'Penawaran',
                'side-badge',
            ], false);

        $this->actingAs($sales)
            ->get(route('sales.quotations.create'))
            ->assertOk()
            ->assertSee('Pilih Design Request')
            ->assertSee('name="design_request_id"', false)
            ->assertSee($designRequest->code);

        $this->actingAs($sales)
            ->get(route('sales.design-requests.show', $designRequest))
            ->assertOk()
            ->assertDontSeeText('Dimensi Utama')
            ->assertSee('Request & Riwayat Revisi', false)
            ->assertSee('Ajukan Request Revisi')
            ->assertSee('href="#history"', false)
            ->assertSee('href="#documents"', false);

        $this->actingAs($sales)
            ->post(route('sales.design-requests.revision', $designRequest), [
                'notes' => 'Ubah ukuran meja dan hitung ulang HPP.',
            ])
            ->assertRedirect();

        $designRequest->refresh();
        $revision = $designRequest->revisionRequests()->firstOrFail();

        $this->assertSame('revision_requested', $designRequest->status);
        $this->assertSame('requested', $revision->status);
        $this->assertSame(0.0, (float) $designRequest->cost_total);
        $this->assertCount(0, $designRequest->items);
        $this->assertSame('completed', $revision->snapshot['status']);

        $this->actingAs($production)
            ->post(route('drafter.design-requests.feedback', $designRequest), ['action' => 'save'])
            ->assertSessionHasErrors('action');

        $this->actingAs($drafter)
            ->post(route('documents.store'), [
                'documentable_type' => DesignRequest::class,
                'documentable_id' => $designRequest->id,
                'name' => 'Drawing Revisi 1',
                'category' => 'request_drawing',
                'replaces_document_id' => $initialDocument->id,
                'revision_note' => 'Ukuran meja diperbarui.',
                'file' => UploadedFile::fake()->create('drawing-revisi-1.pdf', 36, 'application/pdf'),
            ])
            ->assertRedirect();

        $revision->refresh();
        $designRequest->refresh();

        $this->assertSame('drawing_uploaded', $revision->status);
        $this->assertNotNull($revision->drawing_uploaded_at);
        $this->assertSame('revision_drawing_uploaded', $designRequest->status);
        $this->assertFalse($initialDocument->fresh()->is_current);
        $this->assertDatabaseHas('documents', [
            'parent_document_id' => $initialDocument->id,
            'revision_number' => 2,
            'is_current' => 1,
        ]);
        $this->assertSame(
            'Rev 1',
            Document::where('parent_document_id', $initialDocument->id)->firstOrFail()->revisionLabel()
        );

        $this->actingAs($drafter)
            ->delete(route('documents.destroy', $initialDocument))
            ->assertStatus(422);
        $this->assertDatabaseHas('documents', ['id' => $initialDocument->id]);

        $this->actingAs($production)
            ->post(route('drafter.design-requests.feedback', $designRequest), [
                'cost_material' => 1400000,
                'cost_production' => 700000,
                'cost_installation' => 300000,
                'technical_note' => 'Spesifikasi dan HPP revisi.',
                'items' => [[
                    'name' => 'Meja Lab Revisi',
                    'qty' => 1,
                    'unit' => 'Unit',
                    'unit_price' => 2400000,
                ]],
                'action' => 'submit',
            ])
            ->assertRedirect(route('drafter.design-requests.index'));

        $this->assertSame('completed', $designRequest->fresh()->status);
        $this->assertSame(2400000.0, (float) $designRequest->fresh()->cost_total);
        $this->assertSame('completed', $revision->fresh()->status);
        $this->assertNotNull($revision->fresh()->completed_at);
        $this->assertSame('Meja Lab Revisi', $designRequest->fresh()->items()->first()->name);
    }
}
