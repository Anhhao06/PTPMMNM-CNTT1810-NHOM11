<?php
declare(strict_types=1);
function canComplete(array $session, int $requestedEventId, int $now, int $ttl): bool {
    // TTL must be positive
    if ($ttl <= 0) {
        return false;
    }

    // Must have draft with event_id and created_at
    if (!isset($session['draft']['data']['event_id'], $session['draft']['created_at'])) {
        return false;
    }

    // Must match requested event ID
    if ($session['draft']['data']['event_id'] !== $requestedEventId) {
        return false;
    }

    $createdAt = $session['draft']['created_at'];

    // created_at must not be in the future
    if ($createdAt > $now) {
        return false;
    }

    // Age of draft must be strictly less than TTL
    if (($now - $createdAt) >= $ttl) {
        return false;
    }

    return true;
}
