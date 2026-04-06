<?php

use App\Actions\Ticket\CloseTicketAction;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

test('can close an open ticket', function (): void {
    $ticket = Ticket::factory()->open()->create();

    $action = new CloseTicketAction;
    $closed = $action->execute($ticket);

    expect($closed->status)->toBe('closed');
    expect($closed->closed_at)->not->toBeNull();
});

test('sets closed_at timestamp when closing', function (): void {
    $ticket = Ticket::factory()->open()->create();

    $action = new CloseTicketAction;
    $closed = $action->execute($ticket);

    expect($closed->closed_at)->toBeInstanceOf(Carbon::class);
});
