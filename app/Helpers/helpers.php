<?php

if (!function_exists('toUpper')) {
    function toUpper($value)
    {
        return $value ? strtoupper($value) : null;
    }
}
