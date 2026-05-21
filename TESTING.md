# Test Documentation

## Test Database Configuration

Tests can be configured to use a test database. Set environment variables:

```bash
DB_HOST=localhost
DB_NAME=meetrooms_test
DB_USER=root
DB_PASSWORD=
```

Or update `phpunit.xml`:

```xml
<env name="DB_NAME" value="meetrooms_test" />
```

## Writing New Tests

### Unit Test Template
```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue(true);
    }
}
```

### Test Naming Conventions
- Test classes end with `Test`: `MyClassTest.php`
- Test methods start with `test`: `testMethodName()`
- Use descriptive names: `testGetAllReturnsArray()`
- Include expected behavior in name

### Assertions
```php
// Boolean assertions
$this->assertTrue($result);
$this->assertFalse($result);

// Type assertions
$this->assertIsArray($result);
$this->assertIsInt($result);
$this->assertIsString($result);

// Value assertions
$this->assertEquals($expected, $actual);
$this->assertNotEquals($notExpected, $actual);
$this->assertNull($result);
$this->assertNotNull($result);

// Count assertions
$this->assertCount(3, $array);
$this->assertEmpty($array);

// String assertions
$this->assertStringContainsString('needle', 'haystack');
$this->assertMatchesRegularExpression('/pattern/', $string);
```

## Conflict Detection Test Cases

The reservation conflict detection tests verify these scenarios:

1. **No conflict - distinct times**: 10:00-11:00 vs 11:00-12:00
2. **Conflict - overlap**: 10:00-11:00 vs 10:30-11:30
3. **No conflict - exact boundary**: Adjacent reservations
4. **Conflict - new within existing**: Existing 10:00-12:00, new 10:30-11:30
5. **Conflict - new encompasses existing**: Existing 10:30-11:30, new 10:00-12:00
6. **Room ID detection**: Using numeric room ID
7. **Room name detection**: Using room name string
8. **Exclude reservation**: Using `isConflictingExcept()` to exclude a reservation

## Troubleshooting

### Database connection errors
- Ensure database credentials in phpunit.xml match your environment
- Verify test database exists
- Check database server is running

### Reflection errors
- Some tests use mocking with PDO mocks
- Ensure PDO and PDOStatement are properly mocked in test setup

### Test isolation
- Each test should be independent
- Database tables are cleared between tests
- Use `setUp()` to initialize test data

## CI/CD Integration

Example GitHub Actions workflow:

```yaml
- name: Run tests
  run: php vendor/bin/phpunit tests/ --coverage-text
```

## Performance

- Total test suite: ~50 tests
- Expected runtime: < 1 second for unit tests
- Integration tests may take longer depending on database

## Future Improvements

1. Add code coverage reporting
2. Implement fixture loading for test data
3. Add performance benchmarking tests
4. Add property-based testing with Eris
5. Add mutation testing with Infection
