<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function testReturnsAllticketsInIndex()
    {
        Ticket::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/ticket');

        $response->assertOk()
                 ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function testUserCanViewOnlyTheirTickets()
    {
        $myTickets = Ticket::factory()->count(2)->create(['user_id' => $this->user->id]);
        Ticket::factory()->create(); 

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/my-tickets');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function testReturnsUserTicketsById()
    {
        $tickets = Ticket::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/ticket/users/{$this->user->id}");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function testReturnsErrorIfUserNotFoundInUserTicketsById()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/ticket/users/9999');

        $response->assertStatus(404) // controller uses $this->error()
                 ->assertJson(['message' => 'User not found.']);
    }

    public function testAdminCanCreateATicket()
    {
        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'New Ticket',
                    'description' => 'Some details',
                    'status' => 'A',
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'id' => $this->admin->id,
                            'name' => $this->admin->name,
                            'role' => $this->admin->role,
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/ticket', $payload);

        $response->assertCreated()
                 ->assertJsonPath('data.attributes.title', 'New Ticket');

        $this->assertDatabaseHas('tickets', ['title' => 'New Ticket']);
    }

    public function testReturnsErrorIfUserNotFoundWhenCreatingTicket()
    {
        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'Test Ticket',
                    'description' => 'test',
                    'status' => 'H',
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'id' => 9999,
                            'name' => 'ghost',
                            'role' => 'admin',
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/ticket', $payload);

        $response->assertOk()
                 ->assertJsonFragment(['error' => "The provided user id doesn't exist"]);
    }

    public function testCanShowASingleTicket()
    {
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/ticket/{$ticket->id}");

        $response->assertOk()
                 ->assertJsonPath('data.id', $ticket->id);
    }

    public function testReturnsErrorIfTicketNotFound()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/ticket/9999');

        $response->assertStatus(404) 
                 ->assertJson(['message' => 'Ticket not found']);
    }

    public function testCanEditATicket()
    {
        $ticket = Ticket::factory()->create();

        $payload = [
            'data' => [
                'attributes' => [
                    'title' => 'Edited Title',
                    'description' => 'Updated desc',
                    'status' => 'A',
                ],
                'relationships' => [
                    'author' => ['data' => ['id' => $ticket->user_id]]
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
                         ->putJson("/api/v1/ticket/{$ticket->id}", $payload);

        $response->assertOk()
                 ->assertJsonPath('data.attributes.title', 'Edited Title');

        $this->assertDatabaseHas('tickets', ['title' => 'Edited Title']);
    }

    public function testCanUpdateATicket()
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

        $payload = [
            'data' => [
                'attributes' => [
                    'status' => 'X',
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
                         ->patchJson("/api/v1/ticket/{$ticket->id}", $payload);

        $response->assertOk()
                 ->assertJsonPath('data.attributes.status', 'X');

        $this->assertDatabaseHas('tickets', ['status' => 'X']);
    }

    public function testCanDeleteATicket()
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/ticket/{$ticket->id}");

        $response->assertOk()
                 ->assertJson(['message' => 'Ticket Successfully Deleted.']);

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }
}
