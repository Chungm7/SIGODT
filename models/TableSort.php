<?php
/**
 * Strict whitelist for server-side ordering. Request values only select a key
 * from a fixed map; they are never interpolated into SQL.
 */
final class TableSort
{
    /** Returns 'ASC' or 'DESC' for a valid direction, null otherwise. */
    public static function direction($dir)
    {
        if (!is_string($dir)) {
            return null;
        }
        $dir = strtolower($dir);
        return $dir === 'asc' ? 'ASC' : ($dir === 'desc' ? 'DESC' : null);
    }

    /** Returns the key when it exists in the whitelist map, null otherwise. */
    public static function key(array $map, $key)
    {
        return is_string($key) && $key !== '' && array_key_exists($key, $map) ? $key : null;
    }

    /**
     * Builds an ORDER BY body. Unknown keys or directions fall back to $default.
     * "{t}" in expressions is replaced by $prefix (e.g. "page.") to qualify columns.
     */
    public static function orderBy(array $map, $key, $dir, $default, $tieBreaker = '', $prefix = '')
    {
        $key = self::key($map, $key);
        $dir = self::direction($dir);
        if ($key === null || $dir === null) {
            return str_replace('{t}', $prefix, $default);
        }
        $sql = str_replace('{t}', $prefix, $map[$key]) . ' ' . $dir . ' NULLS LAST';
        return $tieBreaker === '' ? $sql : $sql . ', ' . str_replace('{t}', $prefix, $tieBreaker);
    }
}
