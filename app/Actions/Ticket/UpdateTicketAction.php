<?php

namespace App\Actions\Ticket;

use App\Models\Ticket;

class UpdateTicketAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket->fresh();
    }
}
