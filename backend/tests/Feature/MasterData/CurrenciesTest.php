<?php

namespace Tests\Feature\MasterData;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Currencies\Models\Currencies;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrenciesTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing currencies
     */
    public function test_user_can_list_currencies(): void
    {
        $this->authenticateAs();
        Currencies::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/currencies');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Currencies retrieved']);
    }

    /**
     * Test creating a currency
     */
    public function test_user_can_create_currency(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/currencies', [
            'code' => 'USD',
            'name' => 'US Dollar',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Currency created']);

        $this->assertDatabaseHas('currencies', ['code' => 'USD']);
    }

    /**
     * Test creating currency fails with duplicate code
     */
    public function test_create_currency_fails_with_duplicate_code(): void
    {
        $this->authenticateAs();
        Currencies::factory()->create(['code' => 'EUR']);

        $response = $this->postJson('/api/v1/currencies', [
            'code' => 'EUR',
            'name' => 'Euro',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test viewing a currency
     */
    public function test_user_can_view_currency(): void
    {
        $this->authenticateAs();
        $currency = Currencies::factory()->create();

        $response = $this->getJson("/api/v1/currencies/{$currency->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['code' => $currency->code]]);
    }

    /**
     * Test updating a currency
     */
    public function test_user_can_update_currency(): void
    {
        $this->authenticateAs();
        $currency = Currencies::factory()->create();

        $response = $this->patchJson("/api/v1/currencies/{$currency->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'name' => 'Updated Name']);
    }

    /**
     * Test deleting a currency
     */
    public function test_user_can_delete_currency(): void
    {
        $this->authenticateAs();
        $currency = Currencies::factory()->create();

        $response = $this->deleteJson("/api/v1/currencies/{$currency->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    }
}
