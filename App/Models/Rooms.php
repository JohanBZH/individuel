<?php

namespace App\Models;

use Core\Model;

class Rooms extends Model
{
    public static function getAll(): array
    {
        $db = static::getDB();
    $stmt = $db->query('SELECT id, building_id, code, name, capacity, features FROM rooms ORDER BY name ASC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getByBuilding(int $buildingId): array
    {
        $db = static::getDB();
    $stmt = $db->prepare('SELECT id, building_id, code, name, capacity, features FROM rooms WHERE building_id = ? ORDER BY name ASC');
        $stmt->execute([$buildingId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getMapByBuilding(): array
    {
        $all = self::getAll();
        $map = [];
        foreach ($all as $r) {
            $b = (int)$r['building_id'];
            if (!isset($map[$b])) $map[$b] = [];
            $map[$b][] = $r;
        }
        return $map;
    }

    public static function findNameById(int $roomId): ?string
    {
        $db = static::getDB();
        $stmt = $db->prepare('SELECT name FROM rooms WHERE id = ? LIMIT 1');
        $stmt->execute([$roomId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['name'] : null;
    }
}
