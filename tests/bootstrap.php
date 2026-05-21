<?php

/**
 * Test Bootstrap File
 * 
 * This file is executed before any tests run and sets up
 * the test environment and mocking infrastructure.
 */

// Define test mode
define('TEST_MODE', true);

// Require the composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Create a simple test database mock that can be injected
class TestDatabaseConnection {
    private static $instance = null;
    private $statements = [];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function prepare($query) {
        $stmt = new \stdClass();
        $stmt->query = $query;
        $stmt->bindings = [];
        $stmt->results = [];
        return $stmt;
    }

    public function query($query) {
        $stmt = new \stdClass();
        $stmt->query = $query;
        $stmt->results = [];
        return $stmt;
    }

    public function lastInsertId() {
        return '1';
    }

    public function setAttribute($attribute, $value) {
        // Mock setAttribute
    }
}
