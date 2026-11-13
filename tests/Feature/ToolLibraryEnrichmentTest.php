<?php

namespace Tests\Feature;

use App\Enums\ToolVersionStatus;
use App\Models\User;
use App\Modules\ToolManagement\Http\Models\Tool;
use App\Modules\ToolManagement\Http\Models\ToolVersion;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolLibraryEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tool_with_purpose(): void
    {
        $user = User::factory()->create();

        $tool = Tool::create([
            'code' => 'DIR',
            'name' => 'Diagnostic Intake Register™',
            'description' => 'Registre de collecte des informations du diagnostic.',
            'purpose' => 'Recenser, organiser et suivre les informations nécessaires à la réalisation du diagnostic.',
            'category' => 'atomique',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('tools', [
            'code' => 'DIR',
            'purpose' => 'Recenser, organiser et suivre les informations nécessaires à la réalisation du diagnostic.',
        ]);
        $this->assertSame('Recenser, organiser et suivre les informations nécessaires à la réalisation du diagnostic.', $tool->purpose);
    }

    public function test_can_create_version_with_enriched_json_blocks_and_interface_type(): void
    {
        $user = User::factory()->create();

        $tool = Tool::create([
            'code' => 'DIR',
            'name' => 'Diagnostic Intake Register™',
            'category' => 'atomique',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $version = ToolVersion::create([
            'tool_id' => $tool->id,
            'version_number' => '1.0',
            'definition' => [
                'sections' => [
                    ['code' => 'identification', 'label' => 'Identification'],
                    ['code' => 'information', 'label' => 'Information recherchée'],
                ],
            ],
            'configuration_schema' => [
                'allow_custom_priorities' => false,
                'default_status' => 'to_obtain',
            ],
            'business_rules' => [
                'auto_verification' => false,
                'require_justification' => true,
            ],
            'interface_type' => 'intake_register',
            'output_definition' => [
                'type' => 'collection_register',
                'format' => 'tabular',
            ],
            'status' => ToolVersionStatus::Published,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('tool_versions', [
            'interface_type' => 'intake_register',
            'version_number' => '1.0',
        ]);

        // Vérification des casts JSON sous forme de tableaux PHP
        $this->assertIsArray($version->definition);
        $this->assertIsArray($version->configuration_schema);
        $this->assertIsArray($version->business_rules);
        $this->assertIsArray($version->output_definition);
        $this->assertSame('intake_register', $version->interface_type);
        $this->assertFalse($version->configuration_schema['allow_custom_priorities']);
        $this->assertTrue($version->business_rules['require_justification']);
    }

    public function test_designates_current_version_and_maintains_latest_version(): void
    {
        $user = User::factory()->create();

        $tool = Tool::create([
            'code' => 'DSC',
            'name' => 'Diagnostic Scope Canvas™',
            'category' => 'atomique',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        // Version 1.0 publiée
        $v1 = ToolVersion::create([
            'tool_id' => $tool->id,
            'version_number' => '1.0',
            'definition' => ['domains' => ['Finance', 'Organisation']],
            'interface_type' => 'scope_canvas',
            'status' => ToolVersionStatus::Published,
            'published_at' => now()->subDays(5),
            'created_by' => $user->id,
        ]);

        // On désigne V1 comme version courante officielle
        $tool->update(['current_version_id' => $v1->id]);

        // Version 2.0 en brouillon (créée plus récemment)
        $v2 = ToolVersion::create([
            'tool_id' => $tool->id,
            'version_number' => '2.0',
            'definition' => ['domains' => ['Finance', 'Organisation', 'Gouvernance']],
            'interface_type' => 'scope_canvas',
            'status' => ToolVersionStatus::Draft,
            'created_by' => $user->id,
        ]);

        $tool->refresh();

        // currentVersion pointe sur V1 (la version officielle publiée)
        $this->assertSame($v1->id, $tool->currentVersion->id);
        $this->assertSame('1.0', $tool->currentVersion->version_number);

        // latestVersion pointe sur V2 (la plus récemment créée)
        $this->assertSame($v2->id, $tool->latestVersion->id);
        $this->assertSame('2.0', $tool->latestVersion->version_number);
    }

    public function test_cannot_delete_version_designated_as_current(): void
    {
        $user = User::factory()->create();

        $tool = Tool::create([
            'code' => 'MFS',
            'name' => 'Mission Framing Sheet™',
            'category' => 'atomique',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $v1 = ToolVersion::create([
            'tool_id' => $tool->id,
            'version_number' => '1.0',
            'definition' => ['sections' => ['context', 'problem']],
            'status' => ToolVersionStatus::Published,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        $tool->update(['current_version_id' => $v1->id]);

        // Supprimer la version courante doit lever une QueryException (restrictOnDelete)
        $this->expectException(QueryException::class);
        $v1->delete();
    }
}
