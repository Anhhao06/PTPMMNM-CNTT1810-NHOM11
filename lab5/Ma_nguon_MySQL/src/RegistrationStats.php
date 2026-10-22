<?php

declare(strict_types=1);
require_once __DIR__ . '/Database.php';
function registrationStats(mysqli $connection, int $eventId): array
{
    $sql = 'SELECT e.id AS event_id, e.title, COUNT(r.id) AS total
            FROM events e
            LEFT JOIN registrations r ON e.id = r.event_id
            WHERE e.id = ?
            GROUP BY e.id, e.title';
    $statement = queryParams($connection, $sql, [$eventId]);
    try {
        $result = $statement->get_result();
        $row = $result->fetch_assoc();
        if (!$row) {
            return [];
        }
        return [
            'event_id' => (int) $row['event_id'],
            'title' => (string) $row['title'],
            'total' => (int) $row['total'],
        ];
    } finally {
        $statement->close();
    }
}
