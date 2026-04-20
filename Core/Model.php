<?php

namespace Core;

use PDO;
use App\Config;

/**
 * Base model
 *
 * PHP version 7.0
 */
abstract class Model
{

    /**
     * Get the PDO database connection
     *
     * @return mixed
     */
    protected static function getDB()
    {
        static $db = null;

        if ($db === null) {
            // Allow database configuration to be overridden via environment variables (useful in Docker)
            $host = getenv('DB_HOST') ?: Config::DB_HOST;
            $name = getenv('DB_NAME') ?: Config::DB_NAME;
            $user = getenv('DB_USER') ?: Config::DB_USER;
            $pass = getenv('DB_PASSWORD') ?: Config::DB_PASSWORD;

            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8';
            $db = new PDO($dsn, $user, $pass);

            // Throw an Exception when an error occurs
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $db;
    }
}
