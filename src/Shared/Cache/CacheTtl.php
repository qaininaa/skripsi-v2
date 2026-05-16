<?php

namespace Shared\Cache;

/**
 * Centralized TTL (time-to-live) constants in seconds.
 */
final class CacheTtl
{
    /** 24 hours — for master data: rooms, locations */
    public const MASTER = 86400;

    /** 1 hour — for user-specific data */
    public const USER = 3600;

    /** 5 minutes — for search/temporary data */
    public const TEMP = 300;
}
