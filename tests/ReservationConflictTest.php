<?php

namespace Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Models\Reservations;

// Subclass that redirects getDB() to the in-memory SQLite instance
class TestableReservations extends Reservations
{
    private static PDO $testDb;

    public static function setTestDb(PDO $pdo): void
    {
        self::$testDb = $pdo;
    }

    protected static function getDB(): PDO
    {
        return self::$testDb;
    }
}

class ReservationConflictTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        self::$pdo->exec('CREATE TABLE rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            building_id INTEGER,
            code VARCHAR(50),
            name VARCHAR(255) NOT NULL,
            capacity INTEGER,
            features TEXT
        )');

        self::$pdo->exec('CREATE TABLE reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER,
            room VARCHAR(255),
            start_datetime DATETIME NOT NULL,
            end_datetime DATETIME NOT NULL,
            user_id INTEGER NOT NULL,
            comment TEXT,
            created_at DATETIME NOT NULL
        )');

        self::$pdo->exec("INSERT INTO rooms (id, name) VALUES (1, 'Room A'), (2, 'Room B')");

        TestableReservations::setTestDb(self::$pdo);
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM reservations');
    }

    private function insert(int $roomId, string $start, string $end, int $id = null): int
    {
        $idClause = $id ? "id, " : "";
        $idValue  = $id ? "$id, " : "";
        self::$pdo->exec("INSERT INTO reservations ({$idClause}room_id, start_datetime, end_datetime, user_id, created_at)
            VALUES ({$idValue}$roomId, '$start', '$end', 1, '2025-01-01 00:00:00')");
        return (int) self::$pdo->lastInsertId();
    }

    // --- isConflicting ---

    public function testNoConflictWhenNoReservationsExist()
    {
        $this->assertFalse(TestableReservations::isConflicting(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00'));
    }

    public function testNoConflictWhenNewSlotStartsExactlyAtExistingEnd()
    {
        // Existing: 10:00–11:00 | New: 11:00–12:00 — touching but not overlapping
        $this->insert(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00');
        $this->assertFalse(TestableReservations::isConflicting(1, '2025-10-30 11:00:00', '2025-10-30 12:00:00'));
    }

    public function testNoConflictWhenNewSlotEndsExactlyAtExistingStart()
    {
        // Existing: 12:00–13:00 | New: 11:00–12:00 — touching but not overlapping
        $this->insert(1, '2025-10-30 12:00:00', '2025-10-30 13:00:00');
        $this->assertFalse(TestableReservations::isConflicting(1, '2025-10-30 11:00:00', '2025-10-30 12:00:00'));
    }

    public function testNoConflictForDifferentRoom()
    {
        // Existing on room 1, checking room 2 for the same slot
        $this->insert(1, '2025-10-30 10:00:00', '2025-10-30 12:00:00');
        $this->assertFalse(TestableReservations::isConflicting(2, '2025-10-30 10:00:00', '2025-10-30 12:00:00'));
    }

    public function testConflictOnPartialOverlapAfter()
    {
        // Existing: 10:00–12:00 | New: 11:00–13:00
        $this->insert(1, '2025-10-30 10:00:00', '2025-10-30 12:00:00');
        $this->assertTrue(TestableReservations::isConflicting(1, '2025-10-30 11:00:00', '2025-10-30 13:00:00'));
    }

    public function testConflictOnPartialOverlapBefore()
    {
        // Existing: 11:00–13:00 | New: 10:00–12:00
        $this->insert(1, '2025-10-30 11:00:00', '2025-10-30 13:00:00');
        $this->assertTrue(TestableReservations::isConflicting(1, '2025-10-30 10:00:00', '2025-10-30 12:00:00'));
    }

    public function testConflictWhenNewSlotContainsExisting()
    {
        // Existing: 11:00–12:00 | New: 10:00–14:00 — completely wraps existing
        $this->insert(1, '2025-10-30 11:00:00', '2025-10-30 12:00:00');
        $this->assertTrue(TestableReservations::isConflicting(1, '2025-10-30 10:00:00', '2025-10-30 14:00:00'));
    }

    public function testConflictWhenNewSlotIsContainedByExisting()
    {
        // Existing: 10:00–14:00 | New: 11:00–12:00 — new is inside existing
        $this->insert(1, '2025-10-30 10:00:00', '2025-10-30 14:00:00');
        $this->assertTrue(TestableReservations::isConflicting(1, '2025-10-30 11:00:00', '2025-10-30 12:00:00'));
    }

    public function testConflictOnExactSameTimes()
    {
        $this->insert(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00');
        $this->assertTrue(TestableReservations::isConflicting(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00'));
    }

    // --- isConflictingExcept ---

    public function testIsConflictingExceptIgnoresOwnReservation()
    {
        // Editing a reservation should not conflict with itself
        $id = $this->insert(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00');
        $this->assertFalse(TestableReservations::isConflictingExcept(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00', $id));
    }

    public function testIsConflictingExceptStillDetectsOtherConflict()
    {
        // Own reservation excluded, but another one overlaps
        $id = $this->insert(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00');
        $this->insert(1, '2025-10-30 10:30:00', '2025-10-30 11:30:00');
        $this->assertTrue(TestableReservations::isConflictingExcept(1, '2025-10-30 10:00:00', '2025-10-30 11:00:00', $id));
    }
}
