<?php

namespace Tests\Feature\MasterData;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Categories\Models\Category;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing categories
     */
    public function test_user_can_list_categories(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        Category::factory()->count(3)->for($organization)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Categories retrieved']);
    }

    /**
     * Test creating a category
     */
    public function test_user_can_create_category(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->postJson('/api/v1/categories', [
            'code' => 'RAW-MAT',
            'name' => 'Raw Materials',
            'type' => 'material',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Category created']);

        $this->assertDatabaseHas('categories', [
            'organization_id' => $organization->id,
            'code' => 'RAW-MAT',
        ]);
    }

    /**
     * Test creating category fails with duplicate code in same organization
     */
    public function test_create_category_fails_with_duplicate_code_in_org(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        Category::factory()->for($organization)->create(['code' => 'DUP-CODE']);

        $response = $this->postJson('/api/v1/categories', [
            'code' => 'DUP-CODE',
            'name' => 'Duplicate',
            'type' => 'material',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test category can have parent category
     */
    public function test_category_can_have_parent(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $parentCategory = Category::factory()->for($organization)->create();

        $response = $this->postJson('/api/v1/categories', [
            'code' => 'SUB-CAT',
            'name' => 'Sub Category',
            'type' => 'material',
            'parent_id' => $parentCategory->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('categories', [
            'parent_id' => $parentCategory->id,
            'code' => 'SUB-CAT',
        ]);
    }

    /**
     * Test viewing a category
     */
    public function test_user_can_view_category(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $category = Category::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['code' => $category->code]]);
    }

    /**
     * Test updating a category
     */
    public function test_user_can_update_category(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $category = Category::factory()->for($organization)->create();

        $response = $this->patchJson("/api/v1/categories/{$category->id}", [
            'name' => 'Updated Category Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name',
        ]);
    }

    /**
     * Test deleting a category
     */
    public function test_user_can_delete_category(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $category = Category::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    /**
     * Test different category types
     */
    public function test_category_types_persisted_correctly(): void
    {
        $user = $this->authenticateAs();

        $types = ['material', 'product', 'bom', 'other'];

        foreach ($types as $type) {
            $this->postJson('/api/v1/categories', [
                'code' => "TYPE-{$type}",
                'name' => ucfirst($type),
                'type' => $type,
            ])->assertStatus(201);

            $this->assertDatabaseHas('categories', ['type' => $type]);
        }
    }
}
