<?php

namespace Tests\Feature\Api\V1;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;
    protected User $admin;
    protected User $user;
    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions WITH sanctum guard
        Permission::create(['name' => 'ticket_view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'ticket_create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'ticket_update', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'ticket_delete', 'guard_name' => 'sanctum']);

        // Create roles WITH sanctum guard
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        $userRole  = Role::create(['name' => 'user',  'guard_name' => 'sanctum']);

        // Give permissions
        $adminRole->givePermissionTo([
            'ticket_view', 'ticket_create', 'ticket_update', 'ticket_delete'
        ]);

        $userRole->givePermissionTo([
            'ticket_view', 'ticket_create', 'ticket_update', 'ticket_delete'
        ]);

        // Create users
        $this->admin = User::factory()->create();
        $this->user  = User::factory()->create();

        // Assign roles
        $this->admin->assignRole('admin');
        $this->user->assignRole('user');
    }


    /** @test */
    public function test_admin_sees_all_tickets()
    {
        Sanctum::actingAs($this->admin);

        Ticket::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function test_user_sees_only_their_tickets()
    {
        Sanctum::actingAs($this->user);

        Ticket::factory()->count(3)->create(['user_id' => $this->user->id]);
        Ticket::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_admin_can_filter_by_user_id()
    {
        Sanctum::actingAs($this->admin);

        Ticket::factory()->count(3)->create(['user_id' => $this->user->id]);
        Ticket::factory()->count(2)->create();

        $response = $this->getJson("/api/v1/tickets?filter[user_id]={$this->user->id}");

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_cannot_filter_to_see_other_users_tickets()
    {
        Sanctum::actingAs($this->user);

        Ticket::factory()->count(2)->create(['user_id' => $this->admin->id]);

        $response = $this->getJson("/api/v1/tickets?filter[user_id]={$this->admin->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_ticket_for_any_user()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'data' => [
                'attributes' => [
                    'title'       => 'Admin Ticket',
                    'description' => 'Created by admin',
                    'status'      => 'pending',
                ],
                'relationships' => [
                    'author' => [
                        'data' => ['id' => $this->user->id]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/tickets', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tickets', [
            'title'   => 'Admin Ticket',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_user_cannot_assign_ticket_to_another_user()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'Invalid Ticket',
                    'description' => 'Wrong assignment attempt',
                    'status' => 'open',
                ],
                'relationships' => [
                    'author' => [
                        'data' => ['id' => $this->admin->id] // invalid
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/tickets', $payload);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_user_ticket_creation_assigns_their_own_user_id()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'data' => [
                'attributes' => [
                    'title'       => 'User Ticket',
                    'description' => 'Created by user',
                    'status'      => 'completed',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/tickets', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tickets', [
            'title'   => 'User Ticket',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_user_can_update_their_ticket_using_put()
    {
        Sanctum::actingAs($this->user);

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'data' => [
                'attributes' => [
                    'title'       => 'Updated',
                    'description' => 'Updated description',
                    'status'      => 'open',
                ]
            ]
        ];

        $response = $this->putJson("/api/v1/tickets/{$ticket->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', [
            'id'    => $ticket->id,
            'title' => 'Updated',
        ]);
    }

    /** @test */
    public function test_user_can_patch_their_ticket()
    {
        Sanctum::actingAs($this->user);

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'Patched Title',
                ]
            ]
        ];

        $response = $this->patchJson("/api/v1/tickets/{$ticket->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', [
            'id'    => $ticket->id,
            'title' => 'Patched Title',
        ]);
    }

    /** @test */
    public function test_user_can_delete_their_ticket()
    {
        Sanctum::actingAs($this->user);

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }

    /** @test */
    public function test_user_cannot_update_another_users_ticket()
    {
        Sanctum::actingAs($this->user);

        $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'Blocked Update',
                ]
            ]
        ];

        $response = $this->patchJson("/api/v1/tickets/{$ticket->id}", $payload);

        $response->assertStatus(403);
    }
}
