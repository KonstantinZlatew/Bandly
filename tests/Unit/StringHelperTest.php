<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test class for string helper functions
 */
class StringHelperTest extends TestCase
{
    /**
     * Test word count calculation
     */
    public function testWordCount(): void
    {
        $text = "This is a test sentence with multiple words";
        $wordCount = str_word_count($text);
        
        $this->assertEquals(8, $wordCount);
    }
    
    /**
     * Test word count with empty string
     */
    public function testWordCountWithEmptyString(): void
    {
        $text = "";
        $wordCount = str_word_count($text);
        
        $this->assertEquals(0, $wordCount);
    }
    
    /**
     * Test word count with special characters
     */
    public function testWordCountWithSpecialCharacters(): void
    {
        $text = "Hello, world! How are you?";
        $wordCount = str_word_count($text);
        
        $this->assertGreaterThan(0, $wordCount);
    }
    
    /**
     * Test JSON encoding and decoding
     */
    public function testJsonEncodeDecode(): void
    {
        $data = [
            'overall_band' => 7.5,
            'TR' => 7.0,
            'CC' => 8.0,
            'LR' => 7.5,
            'GRA' => 7.0
        ];
        
        $encoded = json_encode($data);
        $decoded = json_decode($encoded, true);
        
        $this->assertIsString($encoded);
        $this->assertIsArray($decoded);
        $this->assertEquals($data, $decoded);
        $this->assertEquals(7.5, $decoded['overall_band']);
    }
    
    /**
     * Test date formatting
     */
    public function testDateFormatting(): void
    {
        $timestamp = '2024-01-15 10:30:00';
        $formatted = date('Y-m-d H:i', strtotime($timestamp));
        
        $this->assertIsString($formatted);
        $this->assertEquals('2024-01-15 10:30', $formatted);
    }
    
    /**
     * Test array key existence
     */
    public function testArrayKeyExists(): void
    {
        $array = [
            'can_analyze' => true,
            'reason' => 'Active subscription',
            'has_subscription' => true,
            'credits_remaining' => 0
        ];
        
        $this->assertArrayHasKey('can_analyze', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('has_subscription', $array);
        $this->assertArrayHasKey('credits_remaining', $array);
        $this->assertTrue($array['can_analyze']);
        $this->assertTrue($array['has_subscription']);
    }
}

