<?php

if (!function_exists('toUpper')) {
    function toUpper($value)
    {
        // Kalau NULL → jadikan string kosong
        if (is_null($value)) {
            return '';
        }

        return strtoupper($value);
    }
}

