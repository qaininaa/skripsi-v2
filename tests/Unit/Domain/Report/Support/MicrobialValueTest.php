<?php

namespace Tests\Unit\Domain\Report\Support;

use Domain\Report\Support\MicrobialValue;
use PHPUnit\Framework\TestCase;

class MicrobialValueTest extends TestCase
{
    public function test_valid_values_include_special_values_and_one_to_two_hundred(): void
    {
        $this->assertTrue(MicrobialValue::isValid(null));
        $this->assertTrue(MicrobialValue::isValid(''));
        $this->assertTrue(MicrobialValue::isValid('<1'));
        $this->assertTrue(MicrobialValue::isValid('TNTC'));
        $this->assertTrue(MicrobialValue::isValid('tntc'));
        $this->assertTrue(MicrobialValue::isValid('1'));
        $this->assertTrue(MicrobialValue::isValid('200'));
    }

    public function test_invalid_values_reject_zero_decimals_negative_leading_zero_and_more_than_two_hundred(): void
    {
        $this->assertFalse(MicrobialValue::isValid('0'));
        $this->assertFalse(MicrobialValue::isValid('001'));
        $this->assertFalse(MicrobialValue::isValid('-1'));
        $this->assertFalse(MicrobialValue::isValid('1.5'));
        $this->assertFalse(MicrobialValue::isValid('201'));
    }

    public function test_to_count_rejects_numeric_values_above_two_hundred(): void
    {
        $this->assertSame(200, MicrobialValue::toCount('200'));
        $this->assertNull(MicrobialValue::toCount('201'));
    }
}
