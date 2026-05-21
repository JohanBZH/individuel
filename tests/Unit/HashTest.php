<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utility\Hash;

/**
 * HashTest - Unit tests for Hash utility class
 * 
 * These are pure unit tests that don't require database or mocking.
 * The Hash class provides password hashing and salt generation utilities.
 */
class HashTest extends TestCase
{
    /**
     * Test: generate() creates a SHA256 hash
     */
    public function testGenerateCreatesSHA256Hash()
    {
        $string = 'test_password';
        $salt = 'test_salt';
        $result = Hash::generate($string, $salt);
        
        // SHA256 produces a 64-character hexadecimal string
        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result);
    }

    /**
     * Test: generate() is deterministic - same inputs produce same output
     */
    public function testGenerateProducesConsistentResults()
    {
        $string = 'test_password';
        $salt = 'test_salt';
        
        $result1 = Hash::generate($string, $salt);
        $result2 = Hash::generate($string, $salt);
        
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test: generate() produces different results for different inputs
     */
    public function testGenerateProducesDifferentResultsForDifferentInputs()
    {
        $string1 = 'password1';
        $string2 = 'password2';
        $salt = 'same_salt';
        
        $result1 = Hash::generate($string1, $salt);
        $result2 = Hash::generate($string2, $salt);
        
        $this->assertNotEquals($result1, $result2);
    }

    /**
     * Test: generate() with empty salt still works
     */
    public function testGenerateWithEmptySalt()
    {
        $string = 'test_password';
        $result = Hash::generate($string, '');
        
        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
    }

    /**
     * Test: generate() salt is included in hash computation
     */
    public function testGenerateIncludesSaltInHash()
    {
        $string = 'test_password';
        $salt1 = 'salt1';
        $salt2 = 'salt2';
        
        $result1 = Hash::generate($string, $salt1);
        $result2 = Hash::generate($string, $salt2);
        
        // Different salts should produce different hashes
        $this->assertNotEquals($result1, $result2);
    }

    /**
     * Test: generateSalt() produces string of correct length
     */
    public function testGenerateSaltProducesCorrectLength()
    {
        $length = 16;
        $result = Hash::generateSalt($length);
        
        $this->assertIsString($result);
        $this->assertEquals($length, strlen($result));
    }

    /**
     * Test: generateSalt() produces different strings each time
     */
    public function testGenerateSaltProducesRandomStrings()
    {
        $salt1 = Hash::generateSalt(20);
        $salt2 = Hash::generateSalt(20);
        
        // Salts should be different (extremely unlikely to be same)
        $this->assertNotEquals($salt1, $salt2);
    }

    /**
     * Test: generateSalt() with zero length
     */
    public function testGenerateSaltWithLengthZero()
    {
        $result = Hash::generateSalt(0);
        $this->assertEquals('', $result);
    }

    /**
     * Test: generateSalt() with large length
     */
    public function testGenerateSaltWithLargeLength()
    {
        $length = 256;
        $result = Hash::generateSalt($length);
        
        $this->assertIsString($result);
        $this->assertEquals($length, strlen($result));
    }

    /**
     * Test: generateUnique() produces unique hashes
     */
    public function testGenerateUniqueProducesUniqueHashes()
    {
        $unique1 = Hash::generateUnique();
        $unique2 = Hash::generateUnique();
        
        // Should be different (based on unique ID)
        $this->assertNotEquals($unique1, $unique2);
    }

    /**
     * Test: generateUnique() produces valid hash format
     */
    public function testGenerateUniqueProducesValidHashFormat()
    {
        $result = Hash::generateUnique();
        
        // Should be a SHA256 hash (64 hex characters)
        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result);
    }

    /**
     * Test: generate() with special characters
     */
    public function testGenerateWithSpecialCharacters()
    {
        $string = 'test!@#$%^&*()_+-=[]{}|;:,.<>?';
        $salt = 'salt!@#$%';
        
        $result = Hash::generate($string, $salt);
        
        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
    }

    /**
     * Test: generate() with unicode characters
     */
    public function testGenerateWithUnicodeCharacters()
    {
        $string = 'test_パスワード_测试';
        $salt = 'salt_ソルト';
        
        $result = Hash::generate($string, $salt);
        
        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
    }

    /**
     * Test: Hash consistency across multiple operations
     */
    public function testHashConsistencyAcrossMultipleCalls()
    {
        $string = 'consistency_test';
        $salt = 'test_salt_123';
        
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = Hash::generate($string, $salt);
        }
        
        // All results should be identical
        $this->assertTrue(count(array_unique($results)) === 1);
    }
}
