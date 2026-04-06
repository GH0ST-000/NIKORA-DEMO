<?php

namespace App\Actions\Ticket;

use App\Models\Ticket;

class DeleteTicketAction
{
    public function execute(Ticket $ticket): bool
    {
        return (bool) $ticket->delete();
    }
}
