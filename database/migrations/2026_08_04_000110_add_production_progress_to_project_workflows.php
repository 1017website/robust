<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            if (! Schema::hasColumn('project_workflows', 'production_progress')) {
                $table->unsignedTinyInteger('production_progress')->default(0)->after('production_status');
            }
            if (! Schema::hasColumn('project_workflows', 'production_note')) {
                $table->text('production_note')->nullable()->after('production_report_completed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            foreach (['production_progress', 'production_note'] as $column) {
                if (Schema::hasColumn('project_workflows', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
