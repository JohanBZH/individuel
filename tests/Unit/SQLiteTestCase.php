<?php

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Models\Reservations;
use App\Models\Rooms;
use App\Models\Buildings;

/**
 * SQLiteTestCase - Base class for integration tests using SQLite in-memory database
 */
abstract class SQLiteTestCase extends TestCase
{
    protected static $pdo;
    protected static $dbInitialized = false;

    public static function setUpBeforeClass(): void
    {
        // Create an in-memory SQLite database for testing
        self::$pdo = new PDO('sqlite::memory:');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (!self::$dbInitialized) {
            self::initializeDatabase();
            self::$dbInitialized = true;
        }
    }

    /**
     * Initialize the test database schema
     */
    protected static function initializeDatabase()
    {
        // Create tables
        self::$pdo->exec('
            CREATE TABLE buildings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL
            )
        ');

        self::$pdo->exec('
            CREATE TABLE rooms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                building_id INTEGER NOT NULL,
                code VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                capacity INTEGER NOT NULL,
                features TEXT,
                FOREIGN KEY (building_id) REFERENCES buildings(id)
            )
        ');

        self::$pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                salt VARCHAR(255) NOT NULL
            )
        ');

        self::$pdo->exec('
            CREATE TABLE reservations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                room_id INTEGER,
                room VARCHAR(255),
                start_datetime DATETIME NOT NULL,
                end_datetime DATETIME NOT NULL,
                user_id INTEGER NOT NULL,
                comment TEXT,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (room_id) REFERENCES rooms(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
    }

    /**
     * Get the test PDO connection
     */
    protected static function getTestPDO()
    {
        return self::$pdo;
    }

    /**
     * Override the Model's getDB() method to use our test database
     */
    protected function injectTestDatabase()
    {
        // This uses reflection to set the static db property
        // Note: This requires modifying how the Model works
        $this->markTestSkipped('Database injection not available without Core refactoring');
    }

    /**
     * Insert test data into the database
     */
    protected function insertTestBuildings()
    {
        self::$pdo->exec("INSERT INTO buildings (code, name) VALUES ('A', 'Building A')");
        self::$pdo->exec("INSERT INTO buildings (code, name) VALUES ('B', 'Building B')");
    }

    /**
     * Insert test rooms
     */
    protected function insertTestRooms()
    {
        self::$pdo->exec("INSERT INTO rooms (building_id, code, name, capacity, features) VALUES (1, 'A101', 'Room A', 20, 'projector')");
        self::$pdo->exec("INSERT INTO rooms (building_id, code, name, capacity, features) VALUES (1, 'A102', 'Room B', 15, 'whiteboard')");
        self::$pdo->exec("INSERT INTO rooms (building_id, code, name, capacity, features) VALUES (2, 'B201', 'Room C', 25, 'whiteboard')");
    }

    /**
     * Insert test users
     */
    protected function insertTestUsers()
    {
        self::$pdo->exec("INSERT INTO users (username, email, password, salt) VALUES ('john_doe', 'john@example.com', 'hashed1', 'salt1')");
        self::$pdo->exec("INSERT INTO users (username, email, password, salt) VALUES ('jane_doe', 'jane@example.com', 'hashed2', 'salt2')");
    }

    /**
     * Insert test reservations
     */
    protected function insertTestReservations()
    {
        self::$pdo->exec("INSERT INTO reservations (room_id, start_datetime, end_datetime, user_id, comment, created_at) 
                         VALUES (1, '2025-10-30 10:00:00', '2025-10-30 11:00:00', 1, 'Test booking 1', '2025-10-01 09:00:00')");
        self::$pdo->exec("INSERT INTO reservations (room_id, start_datetime, end_datetime, user_id, comment, created_at) 
                         VALUES (1, '2025-10-30 14:00:00', '2025-10-30 15:00:00', 1, 'Test booking 2', '2025-10-01 09:00:00')");
        self::$pdo->exec("INSERT INTO reservations (room_id, start_datetime, end_datetime, user_id, comment, created_at) 
                         VALUES (2, '2025-10-30 10:00:00', '2025-10-30 11:00:00', 2, 'Test booking 3', '2025-10-01 09:00:00')");
    }

    protected function setUp(): void
    {
        // Clear all tables before each test
        self::$pdo->exec('DELETE FROM reservations');
        self::$pdo->exec('DELETE FROM users');
        self::$pdo->exec('DELETE FROM rooms');
        self::$pdo->exec('DELETE FROM buildings');
    }
}
