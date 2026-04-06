<?php

use App\Actions\Ticket\DeleteTicketAction;
use App\Models\Ticket;

test('can delete a ticket', function (): void {
    $ticket = Ticket::factory()->create();

    $action = new DeleteTicketAction;
    $result = $action->execute($ticket);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
});
