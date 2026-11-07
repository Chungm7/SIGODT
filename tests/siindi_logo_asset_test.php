<?php
// The unchanged SIINDI screens must resolve the existing SIGODT institutional logo.
$root = dirname(__DIR__);
$source = $root . '/public/img/logo_mpch.png';
$target = $root . '/public/static/illustrations/logomuni3.png';
if (!is_file($target) || !is_readable($target)) {
    fwrite(STDERR, "FAIL: SIINDI institutional logo asset is missing\n");
    exit(1);
}
if (hash_file('sha256', $source) !== hash_file('sha256', $target)
    || getimagesize($target) === false) {
    fwrite(STDERR, "FAIL: SIINDI asset must match the readable SIGODT institutional logo\n");
    exit(1);
}
echo "PASS: SIINDI logo resolves to the SIGODT institutional image\n";
