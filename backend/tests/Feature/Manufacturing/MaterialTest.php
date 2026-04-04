<?php

namespace Tests\Feature\Manufacturing;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Manufacturing\Models\MaterialPrice;
use App\Domain\Units\Models\Units;
use App\Domain\Categories\Models\Category;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing materials
     */
    public function test_user_can_list_materials(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        Material::factory()->count(5)->for($organization)->create();

        $response = $this->getJson('/api/v1/manufacturing/materials');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Materials retrieved']);
    }

    /**
     * Test creating a material
     */
    public function test_user_can_create_material(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $unit = Units::factory()->create();
        $category = Category::factory()->for($organization)->create();

        $response = $this->postJson('/api/v1/manufacturing/materials', [
            'code' => 'MAT-001',
            'name' => 'Steel Rod',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Material created',
                'data' => [
                    'code' => 'MAT-001',
                    'name' => 'Steel Rod',
                ],
            ]);

        $this->assertDatabaseHas('materials', [
            'organization_id' => $organization->id,
            'code' => 'MAT-001',
            'name' => 'Steel Rod',
        ]);
    }

    /**
     * Test material creation fails with duplicate code in same organization
     */
    public function test_create_material_fails_with_duplicate_code(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $unit = Units::factory()->create();

        Material::factory()->for($organization)->create(['code' => 'DUP-CODE']);

        $response = $this->postJson('/api/v1/manufacturing/materials', [
            'code' => 'DUP-CODE',
            'name' => 'Different Material',
            'unit_id' => $unit->id,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test material creation fails with missing unit
     */
    public function test_create_material_fails_with_invalid_unit(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/manufacturing/materials', [
            'code' => 'MAT-INVALID',
            'name' => 'Invalid Material',
            'unit_id' => 'nonexistent-uuid',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test viewing a material
     */
    public function test_user_can_view_material(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/manufacturing/materials/{$material->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Material retrieved',
                'data' => [
                    'id' => $material->id,
                    'code' => $material->code,
                ],
            ]);
    }

    /**
     * Test updating a material
     */
    public function test_user_can_update_material(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();

        $response = $this->patchJson("/api/v1/manufacturing/materials/{$material->id}", [
            'name' => 'Updated Material Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Material updated']);

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Updated Material Name',
        ]);
    }

    /**
     * Test deleting a material
     */
    public function test_user_can_delete_material(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/manufacturing/materials/{$material->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Material deleted']);

        $this->assertSoftDeleted('materials', ['id' => $material->id]);
    }

    /**
     * Test multi-tenant isolation - user cannot access other org's material
     */
    public function test_user_cannot_access_other_org_material(): void
    {
        $this->authenticateAs();
        $otherOrg = Organization::factory()->create();
        $otherMaterial = Material::factory()->for($otherOrg)->create();

        $response = $this->getJson("/api/v1/manufacturing/materials/{$otherMaterial->id}");

        $this->assertThat(
            $response->status(),
            $this->logicalOr($this->equalTo(403), $this->equalTo(404))
        );
    }

    /**
     * Test material metadata storage
     */
    public function test_material_metadata_stored_correctly(): void
    {
        $user = $this->authenticateAs();
        $unit = Units::factory()->create();

        $metadata = ['supplier' => 'ABC Corp', 'minimum_qty' => 100];

        $response = $this->postJson('/api/v1/manufacturing/materials', [
            'code' => 'MAT-META',
            'name' => 'Material with Metadata',
            'unit_id' => $unit->id,
            'metadata' => $metadata,
        ]);

        $response->assertStatus(201);

        $material = Material::where('code', 'MAT-META')->first();
        $this->assertEquals($metadata, $material->metadata);
    }
}
