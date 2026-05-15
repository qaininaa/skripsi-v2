<?php

namespace Tests\Unit\Shared\Cache;

use Shared\Cache\CacheKeyPattern;
use PHPUnit\Framework\TestCase;

class CacheKeyPatternTest extends TestCase
{
    public function test_room_all_constant_has_correct_value(): void
    {
        $this->assertSame('master:room:all', CacheKeyPattern::ROOM_ALL);
    }

    public function test_room_all_filtered_constant_has_correct_value(): void
    {
        $this->assertSame('master:room:list:%s', CacheKeyPattern::ROOM_ALL_FILTERED);
    }

    public function test_room_by_id_constant_has_correct_value(): void
    {
        $this->assertSame('master:room:%s', CacheKeyPattern::ROOM_BY_ID);
    }

    public function test_room_keys_registry_constant_has_correct_value(): void
    {
        $this->assertSame('master:room:keys', CacheKeyPattern::ROOM_KEYS_REGISTRY);
    }

    public function test_room_all_filtered_is_valid_sprintf_format_string(): void
    {
        $this->assertStringContainsString('%s', CacheKeyPattern::ROOM_ALL_FILTERED);

        $result = sprintf(CacheKeyPattern::ROOM_ALL_FILTERED, 'abc123');
        $this->assertSame('master:room:list:abc123', $result);
    }

    public function test_room_by_id_is_valid_sprintf_format_string(): void
    {
        $this->assertStringContainsString('%s', CacheKeyPattern::ROOM_BY_ID);

        $result = sprintf(CacheKeyPattern::ROOM_BY_ID, '42');
        $this->assertSame('master:room:42', $result);
    }
}
