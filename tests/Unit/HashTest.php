<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utility\Hash;

class HashTest extends TestCase
{
    public function testGenerateCreatesSHA256Hash()
    {
        $string = 'test_password';
        $salt = 'test_salt';
        $result = Hash::generate($string, $salt);

        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result)); // SHA256 = 64 hex characters
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result);
    }

    public function testGenerateProducesConsistentResults()
    {
        $string = 'test_password';
        $salt = 'test_salt';

        $result1 = Hash::generate($string, $salt);
        $result2 = Hash::generate($string, $salt);

        $this->assertEquals($result1, $result2);
    }

    public function testGenerateProducesDifferentResultsForDifferentInputs()
    {
        $result1 = Hash::generate('password1', 'same_salt');
        $result2 = Hash::generate('password2', 'same_salt');

        $this->assertNotEquals($result1, $result2);
    }

    public function testGenerateWithEmptySalt()
    {
        $result = Hash::generate('test_password', '');

        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
    }

    public function testGenerateIncludesSaltInHash()
    {
        $string = 'test_password';

        $result1 = Hash::generate($string, 'salt1');
        $result2 = Hash::generate($string, 'salt2');

        $this->assertNotEquals($result1, $result2);
    }

    public function testGenerateSaltProducesCorrectLength()
    {
        $length = 16;
        $result = Hash::generateSalt($length);

        $this->assertIsString($result);
        $this->assertEquals($length, strlen($result));
    }

    public function testGenerateSaltProducesRandomStrings()
    {
        $salt1 = Hash::generateSalt(20);
        $salt2 = Hash::generateSalt(20);

        // Theoretically possible to collide, but astronomically unlikely
        $this->assertNotEquals($salt1, $salt2);
    }

    public function testGenerateSaltWithLengthZero()
    {
        $result = Hash::generateSalt(0);
        $this->assertEquals('', $result);
    }

    public function testGenerateSaltWithLargeLength()
    {
        $length = 256;
        $result = Hash::generateSalt($length);

        $this->assertIsString($result);
        $this->assertEquals($length, strlen($result));
    }

    public function testGenerateUniqueProducesUniqueHashes()
    {
        $unique1 = Hash::generateUnique();
        $unique2 = Hash::generateUnique();

        $this->assertNotEquals($unique1, $unique2);
    }

    public function testGenerateUniqueProducesValidHashFormat()
    {
        $result = Hash::generateUnique();

        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result)); // SHA256 = 64 hex characters
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result);
    }

    public function testGenerateWithSpecialCharacters()
    {
        $result = Hash::generate('test!@#$%^&*()_+-=[]{}|;:,.<>?', 'salt!@#$%');

        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
    }

    public function testGenerateWithUnicodeCharacters()
    {
        $result = Hash::generate('test_パスワード_测试', 'salt_ソルト');

        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result));
    }

    public function testHashConsistencyAcrossMultipleCalls()
    {
        $string = 'consistency_test';
        $salt = 'test_salt_123';

        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = Hash::generate($string, $salt);
        }

        $this->assertTrue(count(array_unique($results)) === 1);
    }
}
