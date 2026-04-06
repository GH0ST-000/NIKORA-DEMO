<?php

namespace App\Actions\Ticket;

use App\Models\Ticket;

class CloseTicketAction
{
    public function execute(Ticket $ticket): Ticket
    {
        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $ticket->fresh();
    }
}
