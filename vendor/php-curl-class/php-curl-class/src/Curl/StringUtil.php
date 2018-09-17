<?php

namespace MPBpostModule\Curl;

class StringUtil
{
    /**
     * Return true when $haystack starts with $needle.
     *
     * @access public
     * @param  $haystack
     * @param  $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return \mb_substr($haystack, 0, \mb_strlen($needle)) === $needle;
    }
}
