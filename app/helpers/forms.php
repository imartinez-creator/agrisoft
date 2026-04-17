<?php
/**
 * Helper functions to sanitize and cast POST data.
 */

/**
 * Returns a float value from POST data, handling comma to dot conversion.
 * Returns null if the value is empty or not numeric.
 */
function post_float(string $key): ?float {
    $v = trim((string)($_POST[$key] ?? ''));
    if ($v === '') return null;
    // Handle comma as decimal separator (common in Europe)
    $v = str_replace(',', '.', $v);
    return is_numeric($v) ? (float)$v : null;
}

/**
 * Returns an integer value from POST data.
 * Returns null if the value is empty or not numeric.
 */
function post_int(string $key): ?int {
    $v = trim((string)($_POST[$key] ?? ''));
    if ($v === '') return null;
    return is_numeric($v) ? (int)$v : null;
}
