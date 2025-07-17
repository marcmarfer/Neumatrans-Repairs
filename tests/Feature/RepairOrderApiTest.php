<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\Client;
use App\Models\RepairOrder;

class RepairOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ];
    }

    protected function createClient(): Client
    {
        return Client::create([
            'DNI'           => 'ABC123',
            'name'          => 'Test Client',
            'email'         => 'client@test.com',
            'telephone'     => '123456789',
            'city'          => 'Test City',
            'postal_code'   => '12345',
            'registered_at' => now(),
            'country'       => 'ES',
        ]);
    }

    protected function createRepairOrder(User $user, Client $client, string $status = 'diagnosing'): RepairOrder
    {
        return RepairOrder::create([
            'client_id'   => $client->id,
            'status'      => $status,
            'observations'=> null,
            'created_by'  => $user->id,
            'completed_at'=> null,
        ]);
    }

    /** @test */
    public function unauthorized_requests_are_rejected()
    {
        $user   = User::factory()->create();
        $client = $this->createClient();
        $order  = $this->createRepairOrder($user, $client);

        $this->getJson('/api/repair-orders')->assertStatus(401);
        $this->getJson("/api/repair-orders/{$order->id}")->assertStatus(401);
        $this->patchJson("/api/repair-orders/{$order->id}", ['status' => 'in_repair'])->assertStatus(401);
    }

    /** @test */
    public function can_list_repair_orders()
    {
        $user   = User::factory()->create();
        $client = $this->createClient();

        foreach (range(1, 3) as $i) {
            $this->createRepairOrder($user, $client);
        }

        $response = $this->withHeaders($this->getAuthHeaders($user))
                         ->getJson('/api/repair-orders');

        $response->assertOk()
                 ->assertJsonCount(3);
    }

    /** @test */
    public function can_show_repair_order()
    {
        $user   = User::factory()->create();
        $client = $this->createClient();
        $order  = $this->createRepairOrder($user, $client, 'reception');

        $response = $this->withHeaders($this->getAuthHeaders($user))
                         ->getJson("/api/repair-orders/{$order->id}");

        $response->assertOk()
                 ->assertJsonFragment([
                     'id'     => $order->id,
                     'status' => 'reception',
                 ]);
    }

    /** @test */
    public function can_update_repair_order_status()
    {
        $user   = User::factory()->create();
        $client = $this->createClient();
        $order  = $this->createRepairOrder($user, $client, 'diagnosing');

        $response = $this->withHeaders($this->getAuthHeaders($user))
                         ->patchJson("/api/repair-orders/{$order->id}", [
                             'status' => 'in_repair',
                         ]);

        $response->assertOk()
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('repair_orders', [
            'id'     => $order->id,
            'status' => 'in_repair',
        ]);
    }

    /** @test */
    public function validation_error_on_invalid_status()
    {
        $user   = User::factory()->create();
        $client = $this->createClient();
        $order  = $this->createRepairOrder($user, $client, 'diagnosing');

        $response = $this->withHeaders($this->getAuthHeaders($user))
                         ->patchJson("/api/repair-orders/{$order->id}", [
                             'status' => 'invalid_status',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('status');
    }
} 