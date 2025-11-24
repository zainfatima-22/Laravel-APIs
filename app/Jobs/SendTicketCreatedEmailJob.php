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
    public Ticket $ticket;
    public $tries = 3;      
    public $backoff = [5, 30]; 
    public function __construct(Ticket $ticket) { $this->ticket = $ticket; }
    public function handle(): void
    {
        $user = User::find($this->ticket->user_id);
        if (!$user || !$user->email) {
            \Log::warning("SendTicketCreatedEmailJob: target user missing or has no email", ['ticket_id' => $this->ticket->id]);
            return;
        }
        $this->ticket->user->notify(new TicketCreatedNotification($this->ticket));
    }
    public function failed(\Throwable $exception)
    {
        \Log::error('SendTicketCreatedEmailJob failed', [
            'ticket_id' => $this->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
    public function failedd(){
        error_clear_last();
    }
}
