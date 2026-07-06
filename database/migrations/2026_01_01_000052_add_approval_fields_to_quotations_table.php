<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'submitted_for_approval_at')) {
                $table->timestamp('submitted_for_approval_at')->nullable()->after('sent_at');
            }
            if (! Schema::hasColumn('quotations', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('submitted_for_approval_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('quotations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('quotations', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('quotations', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approval_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('quotations', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('quotations', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('quotations', 'revision_note')) {
                $table->text('revision_note')->nullable()->after('rejection_note');
            }
            if (! Schema::hasColumn('quotations', 'customer_response_at')) {
                $table->timestamp('customer_response_at')->nullable()->after('revision_note');
            }
            if (! Schema::hasColumn('quotations', 'customer_response_note')) {
                $table->text('customer_response_note')->nullable()->after('customer_response_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            foreach (['approved_by', 'rejected_by'] as $fk) {
                if (Schema::hasColumn('quotations', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            foreach ([
                'submitted_for_approval_at', 'approved_at', 'approval_note', 'rejected_at',
                'rejection_note', 'revision_note', 'customer_response_at', 'customer_response_note',
            ] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
