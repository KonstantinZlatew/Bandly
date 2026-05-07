<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test class for entitlements-deduct.php functions
 */
class EntitlementsDeductTest extends TestCase
{
    /**
     * Test deductCreditForAnalysis with null user ID
     */
    public function testDeductCreditForAnalysisWithNullUserId(): void
    {
        $result = deductCreditForAnalysis(null);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('User not authenticated', $result['message']);
        $this->assertEquals(0, $result['credits_remaining']);
    }

    /**
     * Test deductCreditForAnalysis with zero user ID
     */
    public function testDeductCreditForAnalysisWithZeroUserId(): void
    {
        $result = deductCreditForAnalysis(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('User not authenticated', $result['message']);
    }

    /**
     * Test that deductCreditForAnalysis returns correct structure
     */
    public function testDeductCreditForAnalysisReturnsCorrectStructure(): void
    {
        // This test will fail if database is not available, but tests structure
        $result = deductCreditForAnalysis(999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('credits_remaining', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
        $this->assertIsInt($result['credits_remaining']);
    }
}
