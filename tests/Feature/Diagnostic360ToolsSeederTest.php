<?php

namespace Tests\Feature;

use App\Enums\ToolVersionStatus;
use App\Models\User;
use App\Modules\ToolManagement\Http\Models\Tool;
use App\Modules\ToolManagement\Http\Models\ToolVersion;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Diagnostic360ToolsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class Diagnostic360ToolsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_throws_exception_if_no_user_exists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aucun utilisateur trouvé');

        $this->seed(Diagnostic360ToolsSeeder::class);
    }

    public function test_seeder_creates_all_ten_atomic_diagnostic_tools(): void
    {
        User::factory()->create();

        $this->seed(Diagnostic360ToolsSeeder::class);

        $expectedCodes = [
            'D360-MFS',
            'D360-DSC',
            'D360-DIR',
            'D360-SIG',
            'D360-D360',
            'D360-PSG',
            'D360-DRD',
            'D360-LDC',
            'D360-DAP',
            'D360-NSR',
        ];

        $this->assertDatabaseCount('tools', 10);
        $this->assertDatabaseCount('tool_versions', 10);

        foreach ($expectedCodes as $code) {
            $tool = Tool::with('currentVersion')->where('code', $code)->first();

            $this->assertNotNull($tool, "L'outil {$code} doit exister en base.");
            $this->assertSame('atomic', $tool->category);
            $this->assertSame('active', $tool->status);
            $this->assertNotEmpty($tool->purpose);
            $this->assertNotNull($tool->current_version_id);

            $version = $tool->currentVersion;
            $this->assertInstanceOf(ToolVersion::class, $version);
            $this->assertSame('1.0', $version->version_number);
            $this->assertSame(ToolVersionStatus::Published, $version->status);
            $this->assertNotNull($version->published_at);
            $this->assertIsArray($version->definition);
            $this->assertNotEmpty($version->interface_type);
            $this->assertIsArray($version->output_definition);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        User::factory()->create();

        $this->seed(Diagnostic360ToolsSeeder::class);
        $this->seed(Diagnostic360ToolsSeeder::class);

        $this->assertDatabaseCount('tools', 10);
        $this->assertDatabaseCount('tool_versions', 10);
    }

    public function test_database_seeder_runs_diagnostic_tools_seeder(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('tools', 10);
        $this->assertDatabaseCount('tool_versions', 10);
    }
}
