<?php

namespace App\Models;

use Core\Model;

class Buildings extends Model {
    public static function getAll() {
        $db = static::getDB();
        $stmt = $db->query('SELECT id, code, name FROM buildings ORDER BY name ASC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
