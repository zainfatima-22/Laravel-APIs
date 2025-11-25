<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Ticket;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTicketCreatedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $ticketId; 
    public $tries = 3;      
    public $backoff = [5, 30];

    public function __construct(int $ticketId)
    {
        $this->ticketId = $ticketId;
    }

    public function handle(): void
    {
        $ticket = Ticket::find($this->ticketId);

        if (!$ticket) {
            \Log::warning("Ticket not found in SendTicketCreatedEmailJob", ['ticket_id' => $this->ticketId]);
            return;
        }

        $user = $ticket->user;

        if (!$user || !$user->email) {
            \Log::warning("SendTicketCreatedEmailJob: User missing or no email", ['ticket_id' => $ticket->id]);
            return;
        }

        $user->notify(new TicketCreatedNotification($ticket));
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('SendTicketCreatedEmailJob failed', [
            'ticket_id' => $this->ticketId,
            'error' => $exception->getMessage(),
        ]);
    }
}
