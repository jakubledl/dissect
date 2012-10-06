<?php

namespace Dissect\Util;

/**
 * Some utility functions.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
abstract class Util
{
    /**
     * Merges two or more sets by values.
     *
     * {a, b} union {b, c} = {a, b, c}
     *
     * @return array The union of given sets.
     */
    public static function union()
    {
        return array_unique(call_user_func_array('array_merge', func_get_args()));
    }

    /**
     * Determines whether two sets have a difference.
     *
     * @param array $first The first set.
     * @param array $second The second set.
     *
     * @return boolean Whether there is a difference.
     */
    public static function different(array $first, array $second)
    {
        return count(array_diff($first, $second)) !== 0;
    }
}
