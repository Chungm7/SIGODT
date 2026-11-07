<?php
// Preserve the original template's public asset URL without changing its scripts.
$root = dirname(__DIR__);
$source = $root . '/public/lib/jquery/jquery.min.js';
$target = $root . '/public/js/jquery.min.js';
if (!is_file($target) || !is_readable($target)) {
    fwrite(STDERR, "FAIL: SIINDI jQuery asset is missing\n");
    exit(1);
}
if (hash_file('sha256', $source) !== hash_file('sha256', $target)) {
    fwrite(STDERR, "FAIL: SIINDI jQuery must match the existing SIGODT version\n");
    exit(1);
}
echo "PASS: SIINDI jQuery asset matches the existing SIGODT version\n";
