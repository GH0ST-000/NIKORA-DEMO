<?php

use App\Actions\Ticket\ReopenTicketAction;
use App\Models\Ticket;

test('can reopen a closed ticket', function (): void {
    $ticket = Ticket::factory()->closed()->create();

    $action = new ReopenTicketAction;
    $reopened = $action->execute($ticket);

    expect($reopened->status)->toBe('open');
    expect($reopened->closed_at)->toBeNull();
});

test('clears closed_at when reopening', function (): void {
    $ticket = Ticket::factory()->closed()->create();

    expect($ticket->closed_at)->not->toBeNull();

    $action = new ReopenTicketAction;
    $reopened = $action->execute($ticket);

    expect($reopened->closed_at)->toBeNull();
});
