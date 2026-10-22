<?php
declare(strict_types=1);
final class RegistrationPolicy {
    public function decide(int $capacity, int $registered,
        bool $open, bool $waitingEnabled): string {
        // Invalid capacity or registered count
        if ($capacity <= 0 || $registered < 0 || $registered > $capacity) {
            return 'rejected';
        }

        // Registration closed
        if (!$open) {
            return 'rejected';
        }

        // Available seats
        if ($registered < $capacity) {
            return 'confirmed';
        }

        // Full capacity: check waiting list
        if ($waitingEnabled) {
            return 'waiting';
        }

        return 'rejected';
    }
}
