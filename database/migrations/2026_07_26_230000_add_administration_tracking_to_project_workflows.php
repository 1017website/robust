<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->text('administration_comment')->nullable()->after('qc_updated_at');
            $table->boolean('payment_confirmation_completed')->default(false)->after('administration_comment');
            $table->boolean('withholding_tax_receipt_completed')->default(false)->after('payment_confirmation_completed');
            $table->foreignId('administration_updated_by')->nullable()->after('withholding_tax_receipt_completed')->constrained('users')->nullOnDelete();
            $table->timestamp('administration_updated_at')->nullable()->after('administration_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('administration_updated_by');
            $table->dropColumn([
                'administration_comment',
                'payment_confirmation_completed',
                'withholding_tax_receipt_completed',
                'administration_updated_at',
            ]);
        });
    }
};
