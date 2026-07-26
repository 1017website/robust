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
            if (! Schema::hasColumn('project_workflows', 'qc_checklist')) {
                $table->json('qc_checklist')->nullable()->after('qc_completed');
            }
            if (! Schema::hasColumn('project_workflows', 'qc_note')) {
                $table->text('qc_note')->nullable()->after('qc_checklist');
            }
            if (! Schema::hasColumn('project_workflows', 'delivery_status')) {
                $table->string('delivery_status')->default('scheduling')->after('qc_updated_at');
            }
            if (! Schema::hasColumn('project_workflows', 'delivery_scheduled_at')) {
                $table->dateTime('delivery_scheduled_at')->nullable()->after('delivery_status');
            }
            if (! Schema::hasColumn('project_workflows', 'pod_path')) {
                $table->string('pod_path')->nullable()->after('delivery_scheduled_at');
            }
            if (! Schema::hasColumn('project_workflows', 'pod_name')) {
                $table->string('pod_name')->nullable()->after('pod_path');
            }
            if (! Schema::hasColumn('project_workflows', 'customer_receiver_name')) {
                $table->string('customer_receiver_name')->nullable()->after('pod_name');
            }
            if (! Schema::hasColumn('project_workflows', 'customer_received_at')) {
                $table->dateTime('customer_received_at')->nullable()->after('customer_receiver_name');
            }
            if (! Schema::hasColumn('project_workflows', 'delivery_note')) {
                $table->text('delivery_note')->nullable()->after('customer_received_at');
            }
        });

        // Pertahankan status workflow lama saat kolom delivery_status mulai dipakai.
        DB::table('project_workflows')
            ->where('delivery_returned_completed', true)
            ->update(['delivery_status' => 'completed']);
        DB::table('project_workflows')
            ->where('delivery_out_completed', true)
            ->where('delivery_returned_completed', false)
            ->update(['delivery_status' => 'delivered']);
    }

    public function down(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            foreach ([
                'qc_checklist',
                'qc_note',
                'delivery_status',
                'delivery_scheduled_at',
                'pod_path',
                'pod_name',
                'customer_receiver_name',
                'customer_received_at',
                'delivery_note',
            ] as $column) {
                if (Schema::hasColumn('project_workflows', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
