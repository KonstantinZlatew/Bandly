<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test class for entitlements-check.php functions
 */
class EntitlementsCheckTest extends TestCase
{
    /**
     * Test checkCanAnalyze with null user ID
     */
    public function testCheckCanAnalyzeWithNullUserId(): void
    {
        $result = checkCanAnalyze(null);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['can_analyze']);
        $this->assertEquals('User not authenticated', $result['reason']);
        $this->assertFalse($result['has_subscription']);
        $this->assertEquals(0, $result['credits_remaining']);
    }
    
    /**
     * Test checkCanAnalyze with zero user ID
     */
    public function testCheckCanAnalyzeWithZeroUserId(): void
    {
        $result = checkCanAnalyze(0);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['can_analyze']);
        $this->assertEquals('User not authenticated', $result['reason']);
    }
    
    /**
     * Test that checkCanAnalyze returns correct structure
     */
    public function testCheckCanAnalyzeReturnsCorrectStructure(): void
    {
        // This test will fail if database is not available, but tests structure
        $result = checkCanAnalyze(999999);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('can_analyze', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('has_subscription', $result);
        $this->assertArrayHasKey('credits_remaining', $result);
        $this->assertIsBool($result['can_analyze']);
        $this->assertIsString($result['reason']);
        $this->assertIsBool($result['has_subscription']);
        $this->assertIsInt($result['credits_remaining']);
    }
}

