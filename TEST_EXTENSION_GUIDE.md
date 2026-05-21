# Test Suite Extension Guide

## Current Status
- ✅ 15 unit tests passing
- ✅ Hash utility fully tested
- ✅ Conflict detection framework documented
- ⏳ Model integration tests ready for implementation

## How to Extend the Test Suite

### Step 1: Set Up Integration Test Database

Create a test database:
```bash
mysql -u root -p
CREATE DATABASE meetrooms_test;
EXIT;
```

### Step 2: Load Test Schema

```bash
mysql -u root -p meetrooms_test < sql/init_db.sql
```

### Step 3: Update phpunit.xml

```xml
<env name="DB_HOST" value="localhost" />
<env name="DB_NAME" value="meetrooms_test" />
<env name="DB_USER" value="root" />
<env name="DB_PASSWORD" value="your_password" />
```

### Step 4: Create Model Tests

#### Example: Rooms Model Test

Create `tests/Integration/RoomsModelTest.php`:

```php
<?php

namespace Tests\Integration;

use App\Models\Rooms;
use PHPUnit\Framework\TestCase;
use PDO;

class RoomsModelTest extends TestCase
{
    private static $pdo;

    public static function setUpBeforeClass(): void
    {
        // Connect to test database
        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'meetrooms_test';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host=$host;dbname=$name;charset=utf8";
        self::$pdo = new PDO($dsn, $user, $pass);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function setUp(): void
    {
        // Clean test data before each test
        self::$pdo->exec('DELETE FROM reservations');
        self::$pdo->exec('DELETE FROM rooms');
        self::$pdo->exec('DELETE FROM buildings');

        // Insert test data
        self::$pdo->exec("INSERT INTO buildings (code, name) VALUES ('A', 'Building A')");
        self::$pdo->exec("INSERT INTO rooms (building_id, code, name, capacity, features) 
                         VALUES (1, 'A101', 'Room A', 20, 'projector')");
    }

    /**
     * Test: getAll returns array of rooms
     */
    public function testGetAllReturnsRooms()
    {
        $result = Rooms::getAll();
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('Room A', $result[0]['name']);
    }

    /**
     * Test: getByBuilding returns rooms for specific building
     */
    public function testGetByBuildingReturnsRooms()
    {
        $result = Rooms::getByBuilding(1);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Room A', $result[0]['name']);
    }

    /**
     * Test: findNameById returns room name
     */
    public function testFindNameByIdReturnsName()
    {
        $name = Rooms::findNameById(1);
        
        $this->assertEquals('Room A', $name);
    }

    /**
     * Test: getMapByBuilding groups rooms by building
     */
    public function testGetMapByBuildingGroupsRooms()
    {
        $map = Rooms::getMapByBuilding();
        
        $this->assertIsArray($map);
        $this->assertArrayHasKey(1, $map);
        $this->assertCount(1, $map[1]);
    }
}
```

#### Example: Reservations Model Test

Create `tests/Integration/ReservationsModelTest.php`:

```php
<?php

namespace Tests\Integration;

use App\Models\Reservations;
use PHPUnit\Framework\TestCase;
use PDO;

class ReservationsModelTest extends TestCase
{
    private static $pdo;
    private $roomId = 1;
    private $userId = 1;

    public static function setUpBeforeClass(): void
    {
        // Connect to test database
        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'meetrooms_test';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host=$host;dbname=$name;charset=utf8";
        self::$pdo = new PDO($dsn, $user, $pass);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function setUp(): void
    {
        // Clean and reset test data
        self::$pdo->exec('DELETE FROM reservations');
        self::$pdo->exec('DELETE FROM users');
        self::$pdo->exec('DELETE FROM rooms');
        self::$pdo->exec('DELETE FROM buildings');

        // Insert test data
        self::$pdo->exec("INSERT INTO buildings (code, name) VALUES ('A', 'Building A')");
        self::$pdo->exec("INSERT INTO rooms (building_id, code, name, capacity, features) 
                         VALUES (1, 'A101', 'Room A', 20, 'projector')");
        self::$pdo->exec("INSERT INTO users (username, email, password, salt) 
                         VALUES ('test', 'test@example.com', 'hash', 'salt')");
    }

    /**
     * Test: save creates a new reservation
     */
    public function testSaveCreatesReservation()
    {
        $data = [
            'room_id' => $this->roomId,
            'room' => 'Room A',
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime' => '2025-10-30 11:00:00',
            'user_id' => $this->userId,
            'comment' => 'Test reservation'
        ];

        $id = Reservations::save($data);

        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * Test: isConflicting detects overlapping reservations
     */
    public function testIsConflictingDetectsOverlap()
    {
        // Create first reservation
        Reservations::save([
            'room_id' => $this->roomId,
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime' => '2025-10-30 11:00:00',
            'user_id' => $this->userId,
            'comment' => 'First'
        ]);

        // Check for conflict with overlapping time
        $conflict = Reservations::isConflicting(
            $this->roomId,
            '2025-10-30 10:30:00',
            '2025-10-30 11:30:00'
        );

        $this->assertTrue($conflict);
    }

    /**
     * Test: isConflicting allows non-overlapping times
     */
    public function testIsConflictingAllowsNonOverlapping()
    {
        // Create first reservation
        Reservations::save([
            'room_id' => $this->roomId,
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime' => '2025-10-30 11:00:00',
            'user_id' => $this->userId,
            'comment' => 'First'
        ]);

        // Check for conflict with non-overlapping time
        $conflict = Reservations::isConflicting(
            $this->roomId,
            '2025-10-30 11:00:00',
            '2025-10-30 12:00:00'
        );

        $this->assertFalse($conflict);
    }

    /**
     * Test: getByUser returns user's reservations
     */
    public function testGetByUserReturnsUserReservations()
    {
        Reservations::save([
            'room_id' => $this->roomId,
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime' => '2025-10-30 11:00:00',
            'user_id' => $this->userId,
            'comment' => 'Test'
        ]);

        $result = Reservations::getByUser($this->userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test: delete removes a reservation
     */
    public function testDeleteRemovesReservation()
    {
        $id = Reservations::save([
            'room_id' => $this->roomId,
            'start_datetime' => '2025-10-30 10:00:00',
            'end_datetime' => '2025-10-30 11:00:00',
            'user_id' => $this->userId,
            'comment' => 'Test'
        ]);

        Reservations::delete($id);

        $result = Reservations::getOne($id);

        $this->assertEmpty($result);
    }
}
```

### Step 5: Run Integration Tests

```bash
# Run all tests
php vendor/bin/phpunit tests/

# Run integration tests only
php vendor/bin/phpunit tests/Integration/

# Run specific test
php vendor/bin/phpunit tests/Integration/RoomsModelTest.php
```

## Test Templates for Other Models

### User Model Test Template

```php
<?php

namespace Tests\Integration;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    public function testCreateUserCreatesNewUser()
    {
        $data = [
            'username' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'hashed_password',
            'salt' => 'salt_value'
        ];

        $id = User::createUser($data);

        $this->assertIsNumeric($id);
    }

    public function testGetByLoginReturnsUser()
    {
        // Create user first
        User::createUser([
            'username' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'hashed_password',
            'salt' => 'salt_value'
        ]);

        // Retrieve by login
        $user = User::getByLogin('test@example.com');

        $this->assertIsArray($user);
        $this->assertEquals('test@example.com', $user['email']);
    }
}
```

### Buildings Model Test Template

```php
<?php

namespace Tests\Integration;

use App\Models\Buildings;
use PHPUnit\Framework\TestCase;

class BuildingsModelTest extends TestCase
{
    public function testGetAllReturnsBuildings()
    {
        $result = Buildings::getAll();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
}
```

## Best Practices for Integration Tests

1. **Test Isolation**: Clear database before each test
2. **Named Tests**: Use descriptive test method names
3. **Assertions**: Make assertions specific and meaningful
4. **Fixtures**: Use setUp() for test data
5. **Cleanup**: Delete data after tests if needed
6. **Documentation**: Comment on test purpose and scenario

## Running Tests in CI/CD

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: meetrooms_test
          MYSQL_ROOT_PASSWORD: root
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_mysql
      - run: composer install
      - run: php vendor/bin/phpunit tests/
```

## Summary

By following these steps, you can:
1. ✅ Set up integration test environment
2. ✅ Create model-specific tests
3. ✅ Test all CRUD operations
4. ✅ Test business logic with real database
5. ✅ Integrate tests into CI/CD pipeline

The test suite will grow from 15 tests to comprehensive coverage of all models and business logic.
