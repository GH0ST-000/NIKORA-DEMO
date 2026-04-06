<?php

namespace App\Actions\TicketMessage;

use App\Models\TicketMessage;

class CreateTicketMessageAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): TicketMessage
    {
        return TicketMessage::create($data);
    }
}
