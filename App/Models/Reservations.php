<?php

namespace App\Models;

use Core\Model;
use DateTime;

/**
 * Reservations Model
 */
class Reservations extends Model {

    public static function getAll($filter = '') {
        $db = static::getDB();

    $query = 'SELECT r.*, u.username, u.email, rm.name AS room_name, b.name AS building_name,
        COALESCE(rm.name, r.room) AS room,
        rm.features AS room_features,
        TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime) AS duration_minutes
           FROM reservations r
           LEFT JOIN users u ON r.user_id = u.id
           LEFT JOIN rooms rm ON r.room_id = rm.id
           LEFT JOIN buildings b ON rm.building_id = b.id';

        switch ($filter){
            case 'upcoming':
                $query .= ' WHERE r.end_datetime >= NOW() ORDER BY r.start_datetime ASC';
                break;
            case 'past':
                $query .= ' WHERE r.end_datetime < NOW() ORDER BY r.start_datetime DESC';
                break;
            default:
                $query .= ' ORDER BY r.start_datetime DESC';
        }

        $stmt = $db->query($query);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getUpcomingPaged(int $limit, int $offset): array {
        $db = static::getDB();
        $sql = 'SELECT r.*, u.username, u.email, rm.name AS room_name, b.name AS building_name,
                       COALESCE(rm.name, r.room) AS room,
                       rm.features AS room_features,
                       TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime) AS duration_minutes
                FROM reservations r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                LEFT JOIN buildings b ON rm.building_id = b.id
                WHERE r.end_datetime >= NOW()
                ORDER BY r.start_datetime ASC
                LIMIT :limit OFFSET :offset';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function countUpcoming(): int {
        $db = static::getDB();
        $stmt = $db->query('SELECT COUNT(*) AS c FROM reservations WHERE end_datetime >= NOW()');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function getTopRoomsByReservations(int $limit = 5): array {
        $db = static::getDB();
        $sql = 'SELECT rm.id, rm.name, rm.code, rm.capacity, rm.features, b.name AS building_name, b.id AS building_id,
                       COUNT(*) AS reservations_count
                FROM reservations r
                INNER JOIN rooms rm ON r.room_id = rm.id
                INNER JOIN buildings b ON rm.building_id = b.id
                GROUP BY rm.id, rm.name, rm.code, rm.capacity, rm.features, b.name, b.id
                ORDER BY reservations_count DESC
                LIMIT :limit';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getTopBuildingsByReservations(int $limit = 3): array {
        $db = static::getDB();
        $sql = 'SELECT b.id, b.code, b.name, COUNT(*) AS reservations_count
                FROM reservations r
                INNER JOIN rooms rm ON r.room_id = rm.id
                INNER JOIN buildings b ON rm.building_id = b.id
                GROUP BY b.id, b.code, b.name
                ORDER BY reservations_count DESC
                LIMIT :limit';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getOne($id) {
        $db = static::getDB();

      $stmt = $db->prepare('SELECT r.*, u.username, u.email, rm.name AS room_name, b.name AS building_name, b.id AS building_id,
                         COALESCE(rm.name, r.room) AS room,
                         rm.features AS room_features,
                         TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime) AS duration_minutes
                     FROM reservations r
                     LEFT JOIN users u ON r.user_id = u.id
                     LEFT JOIN rooms rm ON r.room_id = rm.id
                     LEFT JOIN buildings b ON rm.building_id = b.id
                     WHERE r.id = ? LIMIT 1');

        $stmt->execute([$id]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

        public static function getByUser($id) {
        $db = static::getDB();

            $stmt = $db->prepare('SELECT r.*, u.username, u.email, rm.name AS room_name, b.name AS building_name,
                         COALESCE(rm.name, r.room) AS room,
                         rm.features AS room_features,
                         TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime) AS duration_minutes
                     FROM reservations r
                                         LEFT JOIN users u ON r.user_id = u.id
                     LEFT JOIN rooms rm ON r.room_id = rm.id
                     LEFT JOIN buildings b ON rm.building_id = b.id
                     WHERE r.user_id = ? ORDER BY r.start_datetime DESC');
        $stmt->execute([$id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getByUserPaged(int $userId, int $limit, int $offset): array {
        $db = static::getDB();
        $sql = 'SELECT r.*, u.username, u.email, rm.name AS room_name, b.name AS building_name,
                         COALESCE(rm.name, r.room) AS room,
                         rm.features AS room_features,
                         TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime) AS duration_minutes
                FROM reservations r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                LEFT JOIN buildings b ON rm.building_id = b.id
                WHERE r.user_id = :uid
                ORDER BY r.start_datetime DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function countByUser(int $userId): int {
        $db = static::getDB();
        $stmt = $db->prepare('SELECT COUNT(*) AS c FROM reservations WHERE user_id = :uid');
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function getByUserInRange($id, $startInclusive, $endInclusive) {
        $db = static::getDB();

        $sql = 'SELECT r.*, rm.name AS room_name, b.name AS building_name,
                         COALESCE(rm.name, r.room) AS room,
                         rm.features AS room_features,
                         TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime) AS duration_minutes
                FROM reservations r
                LEFT JOIN rooms rm ON r.room_id = rm.id
                LEFT JOIN buildings b ON rm.building_id = b.id
                WHERE r.user_id = :uid
                  AND r.start_datetime BETWEEN :start AND :end
                ORDER BY r.start_datetime ASC';

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':start', $startInclusive);
        $stmt->bindValue(':end', $endInclusive);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function isConflicting($roomOrId, $start, $end) {
        $db = static::getDB();

        // If a numeric room id is provided, check by room_id; otherwise fallback to room name
        if (is_numeric($roomOrId)) {
            $stmt = $db->prepare('SELECT COUNT(*) as c FROM reservations WHERE room_id = :room_id AND (start_datetime < :end AND end_datetime > :start)');
            $stmt->bindParam(':room_id', $roomOrId);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) as c FROM reservations WHERE room = :room AND (start_datetime < :end AND end_datetime > :start)');
            $stmt->bindParam(':room', $roomOrId);
        }
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();

        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $res['c'] > 0;
    }

    public static function save($data) {
        $db = static::getDB();

        // If room_id is provided, include it and set room label from rooms table for readability
        $roomId = isset($data['room_id']) && $data['room_id'] !== '' ? (int)$data['room_id'] : null;
        $roomLabel = isset($data['room']) ? $data['room'] : null;
        if ($roomId && !$roomLabel) {
            $stmtLookup = $db->prepare('SELECT name FROM rooms WHERE id = ? LIMIT 1');
            $stmtLookup->execute([$roomId]);
            $roomLabel = ($row = $stmtLookup->fetch(\PDO::FETCH_ASSOC)) ? $row['name'] : null;
        }

        $stmt = $db->prepare('INSERT INTO reservations(room, room_id, start_datetime, end_datetime, user_id, comment, created_at)
                               VALUES (:room, :room_id, :start, :end, :user_id, :comment, :created_at)');

        $created = (new DateTime())->format('Y-m-d H:i:s');

        $stmt->bindParam(':room', $roomLabel);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->bindParam(':start', $data['start_datetime']);
        $stmt->bindParam(':end', $data['end_datetime']);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':comment', $data['comment']);
        $stmt->bindParam(':created_at', $created);

        $stmt->execute();

        return $db->lastInsertId();
    }

    public static function delete($id) {
        $db = static::getDB();

        $stmt = $db->prepare('DELETE FROM reservations WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function update(int $id, array $data): void {
        $db = static::getDB();

        $roomId = isset($data['room_id']) && $data['room_id'] !== '' ? (int)$data['room_id'] : null;
        $roomLabel = isset($data['room']) ? $data['room'] : null;
        if ($roomId && !$roomLabel) {
            $stmtLookup = $db->prepare('SELECT name FROM rooms WHERE id = ? LIMIT 1');
            $stmtLookup->execute([$roomId]);
            $roomLabel = ($row = $stmtLookup->fetch(\PDO::FETCH_ASSOC)) ? $row['name'] : null;
        }

    // Note: the current schema does not include an updated_at column
    // If you add it later, you can re-introduce the timestamp in the UPDATE
    $stmt = $db->prepare('UPDATE reservations SET room = :room, room_id = :room_id, start_datetime = :start, end_datetime = :end, comment = :comment WHERE id = :id');
        $stmt->bindValue(':room', $roomLabel);
        $stmt->bindValue(':room_id', $roomId);
        $stmt->bindValue(':start', $data['start_datetime']);
        $stmt->bindValue(':end', $data['end_datetime']);
        $stmt->bindValue(':comment', $data['comment'] ?? null);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function isConflictingExcept($roomOrId, $start, $end, int $excludeId) {
        $db = static::getDB();

        if (is_numeric($roomOrId)) {
            $stmt = $db->prepare('SELECT COUNT(*) as c FROM reservations WHERE id <> :exclude AND room_id = :room_id AND (start_datetime < :end AND end_datetime > :start)');
            $stmt->bindParam(':room_id', $roomOrId);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) as c FROM reservations WHERE id <> :exclude AND room = :room AND (start_datetime < :end AND end_datetime > :start)');
            $stmt->bindParam(':room', $roomOrId);
        }
        $stmt->bindValue(':exclude', $excludeId, \PDO::PARAM_INT);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $res['c'] > 0;
    }

}
