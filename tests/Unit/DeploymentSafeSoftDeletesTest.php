<?php

namespace Tests\Unit;

use App\Models\Concerns\HasDeploymentSafeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacySoftDeleteModel extends Model
{
    use HasDeploymentSafeSoftDeletes;

    protected $table = 'deployment_safe_legacy_models';
    protected $guarded = [];
    public $timestamps = false;
}

class DeploymentSafeSoftDeletesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('deployment_safe_legacy_models');
        Schema::create('deployment_safe_legacy_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        LegacySoftDeleteModel::query()->getConnection()->table('deployment_safe_legacy_models')->insert(['name' => 'Legacy Admin']);
        LegacySoftDeleteModel::clearBootedModels();
    }

    protected function tearDown(): void
    {
        LegacySoftDeleteModel::clearBootedModels();
        Schema::dropIfExists('deployment_safe_legacy_models');

        parent::tearDown();
    }

    public function test_model_remains_queryable_before_soft_delete_migration_runs(): void
    {
        $this->assertSame('Legacy Admin', LegacySoftDeleteModel::query()->firstOrFail()->name);
        $this->assertStringNotContainsString('deleted_at', LegacySoftDeleteModel::query()->toSql());

        Schema::table('deployment_safe_legacy_models', function (Blueprint $table) {
            $table->softDeletes();
        });
        LegacySoftDeleteModel::clearBootedModels();

        $record = LegacySoftDeleteModel::query()->firstOrFail();
        $this->assertStringContainsString('deleted_at', LegacySoftDeleteModel::query()->toSql());

        $record->delete();

        $this->assertSame(0, LegacySoftDeleteModel::query()->count());
        $this->assertSame(1, LegacySoftDeleteModel::withTrashed()->count());
    }
}
