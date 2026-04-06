<?php

namespace App\Actions\Ticket;

use App\Models\Ticket;

class ReopenTicketAction
{
    public function execute(Ticket $ticket): Ticket
    {
        $ticket->update([
            'status' => 'open',
            'closed_at' => null,
        ]);

        return $ticket->fresh();
    }
}
