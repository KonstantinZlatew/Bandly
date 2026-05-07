<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test class for validation functions
 */
class ValidationTest extends TestCase
{
    /**
     * Test email validation
     */
    public function testEmailValidation(): void
    {
        $validEmail = 'test@example.com';
        $invalidEmail = 'not-an-email';

        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Test integer validation
     */
    public function testIntegerValidation(): void
    {
        $validInt = 123;
        $invalidInt = 'abc';

        $this->assertTrue(is_int($validInt));
        $this->assertFalse(is_int($invalidInt));
        $this->assertTrue(filter_var($validInt, FILTER_VALIDATE_INT) !== false);
        $this->assertFalse(filter_var($invalidInt, FILTER_VALIDATE_INT) !== false);
    }

    /**
     * Test string sanitization
     */
    public function testStringSanitization(): void
    {
        $input = '<script>alert("xss")</script>';
        $sanitized = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('&lt;', $sanitized);
    }

    /**
     * Test array structure validation
     */
    public function testArrayStructureValidation(): void
    {
        $requiredKeys = ['can_analyze', 'reason', 'has_subscription', 'credits_remaining'];
        $testArray = [
            'can_analyze' => true,
            'reason' => 'Test',
            'has_subscription' => false,
            'credits_remaining' => 5
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $testArray);
        }
    }

    /**
     * Test null and empty checks
     */
    public function testNullAndEmptyChecks(): void
    {
        $nullValue = null;
        $emptyString = '';
        $zero = 0;
        $falseValue = false;

        $this->assertTrue(is_null($nullValue));
        $this->assertTrue(empty($emptyString));
        $this->assertTrue(empty($zero));
        $this->assertTrue(empty($falseValue));
        $this->assertFalse(empty('test'));
        $this->assertFalse(empty(1));
    }
}
