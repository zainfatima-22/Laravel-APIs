<?php

namespace App\Http\Controllers\Api\V1;

use Orion\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Policies\TicketPolicy;

class TicketOrionController extends Controller
{
    protected $model = Ticket::class;
    protected $policy = TicketPolicy::class;
}
