<?php
namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public Ticket $ticket;
    public function __construct(Ticket $ticket) { $this->ticket = $ticket; }
    public function via($notifiable) { return ['mail']; }
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Ticket has been created')
            ->view('emails.ticket_created', ['ticket' => $this->ticket]);
    }
}
