<?php

namespace App\Domain;

enum TicketStatus: string
{
    case Waiting = 'waiting';
    case Serving = 'serving';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Skipped = 'skipped';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Waiting => in_array($next, [self::Serving, self::Cancelled], true),
            self::Serving => in_array($next, [self::Completed, self::Skipped], true),
            self::Skipped => $next === self::Serving,
            self::Completed, self::Cancelled => false,
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Waiting, self::Serving], true);
    }
}
