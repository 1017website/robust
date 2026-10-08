<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->unsignedTinyInteger('qc_progress')->default(0);
            $table->boolean('qc_installation_completed')->default(false);
            $table->unsignedTinyInteger('qc_installation_progress')->default(0);
            $table->json('qc_installation_checklist')->nullable();
            $table->text('qc_installation_note')->nullable();
            $table->string('qc_installation_document_path')->nullable();
            $table->string('qc_installation_document_name')->nullable();
            $table->foreignId('qc_installation_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qc_installation_updated_at')->nullable();
        });

        DB::table('project_workflows')->where('qc_completed', true)->update(['qc_progress' => 100]);
    }

    public function down(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->dropForeign(['qc_installation_updated_by']);
            $table->dropColumn([
                'qc_progress', 'qc_installation_completed', 'qc_installation_progress',
                'qc_installation_checklist', 'qc_installation_note', 'qc_installation_document_path',
                'qc_installation_document_name', 'qc_installation_updated_by', 'qc_installation_updated_at',
            ]);
        });
    }
};
