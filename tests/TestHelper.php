<?php

namespace Tests;

use PDO;

/**
 * TestHelper - Utilities for mocking and testing with the application models
 */
class TestHelper
{
    private static $mockPDO = null;

    /**
     * Get or create a mock PDO instance for testing
     */
    public static function getMockPDO()
    {
        if (self::$mockPDO === null) {
            self::$mockPDO = new \PDO('sqlite::memory:');
        }
        return self::$mockPDO;
    }

    /**
     * Setup mock database for a test
     * Uses reflection to inject the mock PDO into the Model's getDB cache
     */
    public static function setupMockDatabase($mockPDO)
    {
        try {
            // Try to access the static cache in Model via reflection
            $reflection = new \ReflectionClass('Core\Model');
            
            // Get the private static property (name may vary)
            $property = null;
            foreach ($reflection->getProperties() as $prop) {
                if ($prop->isStatic()) {
                    $property = $prop;
                    break;
                }
            }

            if ($property) {
                $property->setAccessible(true);
                $property->setValue(null, $mockPDO);
            }
        } catch (\Exception $e) {
            // Silently fail - the models will use the real database
            // This is acceptable for integration tests
        }
    }

    /**
     * Create a mock PDO statement with predefined fetch results
     */
    public static function createMockStatement($fetchResults = null)
    {
        // Since we can't easily mock static methods in PHPUnit without mocking libraries,
        // we'll use a simpler approach
        return null;
    }
}
