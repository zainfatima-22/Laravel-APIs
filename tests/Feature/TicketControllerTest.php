<?php

namespace Tests\Feature\Api\V1;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin and regular user
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /** @test */
    public function testIndexShowsAllTicketsForAdmin()
    {
        Sanctum::actingAs($this->admin);

        Ticket::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    /** @test */
    public function testIndexShowsOnlyUserTicketsForRegularUser()
    {
        Sanctum::actingAs($this->user);

        Ticket::factory()->count(3)->create(['user_id' => $this->user->id]);
        Ticket::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function testGetTicketsForSpecificUser()
    {
        Sanctum::actingAs($this->admin);

        Ticket::factory()->count(4)->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/users/{$this->user->id}/tickets");

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    /** @test */
    public function testGetSpecificUserTicketsUnauthorizedForOtherUsers()
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/users/{$this->user->id}/tickets");

        $response->assertStatus(403);
    }

    /** @test */
    public function testGetSpecificTicketOfUser()
    {
        Sanctum::actingAs($this->user);

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/users/{$this->user->id}/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $ticket->id);
    }

    /** @test */
    public function testGetSpecificTicketOfUserUnauthorized()
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/users/{$this->user->id}/tickets/{$ticket->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function testAdminCanCreateTicketWithUserId()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'Admin Ticket',
                    'description' => 'Created by admin',
                    'status' => 'A',
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'id' => $this->user->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/tickets', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas((new Ticket())->getTable(), [
            'title' => 'Admin Ticket',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function testRegularUserTicketCreationAutomaticallyAssignsUserId()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'User Ticket',
                    'description' => 'Created by user',
                    'status' => 'H',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/tickets', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas((new Ticket())->getTable(), [
            'title' => 'User Ticket',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function testUpdateTicketWithPut()
    {
        Sanctum::actingAs($this->user);

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'Updated Title',
                    'description' => 'Updated description',
                    'status' => 'C',
                ]
            ]
        ];

        $response = $this->putJson("/api/v1/tickets/{$ticket->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas((new Ticket())->getTable(), [
            'id' => $ticket->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function testUpdateTicketWithPatch()
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
        $this->assertDatabaseHas((new Ticket())->getTable(), [
            'id' => $ticket->id,
            'title' => 'Patched Title',
        ]);
    }

    /** @test */
    public function testDeleteTicket()
    {
        Sanctum::actingAs($this->user);

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing((new Ticket())->getTable(), [
            'id' => $ticket->id,
        ]);
    }
}
