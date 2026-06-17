<?php

namespace Tests;

use PDO;

class TestHelper
{
    private static $mockPDO = null;

    public static function getMockPDO()
    {
        if (self::$mockPDO === null) {
            self::$mockPDO = new \PDO('sqlite::memory:');
        }
        return self::$mockPDO;
    }

    /**
     * Uses reflection to inject a PDO instance into Core\Model's static db cache,
     * bypassing the normal getDB() connection flow.
     */
    public static function setupMockDatabase($mockPDO)
    {
        try {
            $reflection = new \ReflectionClass('Core\Model');

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
            // Silently fail — models will fall back to the real database
        }
    }
}
