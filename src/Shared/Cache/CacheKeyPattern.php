<?php

namespace Shared\Cache;

/**
 * Centralized cache key pattern constants for all domain entities.
 *
 * Key registry pattern:
 * - Each domain maintains a registry key (e.g. ROOM_KEYS_REGISTRY) that stores
 *   an array of all active filtered-list cache keys generated at runtime.
 * - On invalidation, the observer reads the registry, forgets every key in it,
 *   then forgets the registry itself and the base list key.
 * - This approach works around the lack of native tag-based flush in the file
 *   cache store.
 */
final class CacheKeyPattern
{
    /** List of all rooms (no filter applied) */
    public const ROOM_ALL = 'master:room:all';

    /** List of rooms with filter — %s is replaced with md5 hash of filter params */
    public const ROOM_ALL_FILTERED = 'master:room:list:%s';

    /** Single room by ID — %s is replaced with the room id */
    public const ROOM_BY_ID = 'master:room:%s';

    /** Registry of all active filtered-list cache keys for the Room domain */
    public const ROOM_KEYS_REGISTRY = 'master:room:keys';
}
