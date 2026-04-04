<?php

namespace Tests\Feature\MasterData;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Taxes\Models\Tax;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxesTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing taxes
     */
    public function test_user_can_list_taxes(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        Tax::factory()->count(3)->for($organization)->create();

        $response = $this->getJson('/api/v1/taxes');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Taxes retrieved']);
    }

    /**
     * Test creating a tax
     */
    public function test_user_can_create_tax(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->postJson('/api/v1/taxes', [
            'code' => 'VAT-10',
            'name' => 'VAT 10%',
            'rate' => 10.00,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Tax created']);

        $this->assertDatabaseHas('taxes', [
            'organization_id' => $organization->id,
            'code' => 'VAT-10',
            'rate' => 10.00,
        ]);
    }

    /**
     * Test tax rate is stored as decimal
     */
    public function test_tax_rate_stored_as_decimal(): void
    {
        $user = $this->authenticateAs();

        $this->postJson('/api/v1/taxes', [
            'code' => 'TAX-DECIMAL',
            'name' => 'Decimal Tax',
            'rate' => 15.75,
            'is_active' => true,
        ])->assertStatus(201);

        $tax = Tax::where('code', 'TAX-DECIMAL')->first();
        $this->assertEquals(15.75, (float)$tax->rate);
    }

    /**
     * Test creating inactive tax
     */
    public function test_user_can_create_inactive_tax(): void
    {
        $user = $this->authenticateAs();

        $response = $this->postJson('/api/v1/taxes', [
            'code' => 'VAT-INACTIVE',
            'name' => 'Inactive Tax',
            'rate' => 5.00,
            'is_active' => false,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('taxes', [
            'code' => 'VAT-INACTIVE',
            'is_active' => false,
        ]);
    }

    /**
     * Test viewing a tax
     */
    public function test_user_can_view_tax(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $tax = Tax::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/taxes/{$tax->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['code' => $tax->code]]);
    }

    /**
     * Test updating a tax
     */
    public function test_user_can_update_tax(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $tax = Tax::factory()->for($organization)->create();

        $response = $this->patchJson("/api/v1/taxes/{$tax->id}", [
            'rate' => 20.00,
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'rate' => 20.00,
            'is_active' => false,
        ]);
    }

    /**
     * Test deleting a tax
     */
    public function test_user_can_delete_tax(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $tax = Tax::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/taxes/{$tax->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('taxes', ['id' => $tax->id]);
    }

    /**
     * Test tax creation fails with missing required fields
     */
    public function test_create_tax_fails_with_missing_rate(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/taxes', [
            'code' => 'INVALID-TAX',
            'name' => 'Invalid Tax',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate']);
    }
}
