<?php
namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ticket_view');
    }

    public function create(User $user): bool
    {
        return $user->can('ticket_create');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket_view') && $ticket->user_id === $user->id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket_update') && $ticket->user_id === $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket_delete') && $ticket->user_id === $user->id;
    }
    public function viewUserTickets(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }
    public function viewUserTicket(User $authUser, Ticket $ticket, User $user): bool
    {
        return $authUser->can('ticket_view') 
               && $ticket->user_id === $authUser->id 
               && $user->id === $authUser->id;
    }
}