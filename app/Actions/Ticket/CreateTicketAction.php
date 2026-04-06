<?php

namespace App\Actions\Ticket;

use App\Models\Ticket;

class CreateTicketAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Ticket
    {
        return Ticket::create(array_merge([
            'status' => 'open',
            'priority' => 'medium',
        ], $data));
    }
}
