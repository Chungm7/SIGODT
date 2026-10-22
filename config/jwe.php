<?php
/**
 * JWE Encryption & Decryption Module
 *
 * Implements RFC 7516 JSON Web Encryption using jose-jwt components
 * with A256KW (AES Key Wrap 256-bit) and A256CBC-HS512 (AES 256 CBC with HMAC SHA-512).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Jose\Component\Core\JWK;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Core\Util\Base64UrlSafe;
use Dotenv\Dotenv;

// Load environment variables safely if present
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

$rawJweSecret = $_ENV['JWE_SECRET'] ?? getenv('JWE_SECRET') ?? '';
$sharedSecret = substr(hash('sha256', (string)$rawJweSecret, true), 0, 32);

$jwk = new JWK([
    'kty' => 'oct',
    'k' => Base64UrlSafe::encodeUnpadded($sharedSecret),
    'use' => 'enc'
]);

$keyEncryptionAlgorithmManager = new AlgorithmManager([
    new A256KW(),
]);

$contentEncryptionAlgorithmManager = new AlgorithmManager([
    new A256CBCHS512(),
]);

$compressionManager = new CompressionMethodManager([]);

if (!function_exists('encryptJWE')) {
    /**
     * Encrypts plaintext payload into a compact serialized JWE token.
     *
     * @param string $payload The plaintext content to encrypt.
     * @param JWK|null $key Optional custom key. Defaults to global $jwk.
     * @return string Compact serialized JWE string.
     */
    function encryptJWE(string $payload, ?JWK $key = null): string {
        global $jwk, $keyEncryptionAlgorithmManager, $contentEncryptionAlgorithmManager, $compressionManager;

        $targetKey = $key ?? $jwk;
        $keyMgr = $keyEncryptionAlgorithmManager ?? new AlgorithmManager([new A256KW()]);
        $contentMgr = $contentEncryptionAlgorithmManager ?? new AlgorithmManager([new A256CBCHS512()]);
        $compMgr = $compressionManager ?? new CompressionMethodManager([]);

        $jweBuilder = new JWEBuilder(
            $keyMgr,
            $contentMgr,
            $compMgr
        );

        $jwe = $jweBuilder
            ->create()
            ->withPayload($payload)
            ->withSharedProtectedHeader([
                'alg' => 'A256KW',
                'enc' => 'A256CBC-HS512'
            ])
            ->addRecipient($targetKey)
            ->build();

        return (new CompactSerializer())->serialize($jwe, 0);
    }
}

if (!function_exists('decryptJWE')) {
    /**
     * Decrypts a compact serialized JWE token.
     *
     * @param string $token Compact serialized JWE string.
     * @param JWK|null $key Optional custom key. Defaults to global $jwk.
     * @return string|false Decrypted plaintext payload, or false on failure.
     */
    function decryptJWE(string $token, ?JWK $key = null) {
        global $jwk, $keyEncryptionAlgorithmManager, $contentEncryptionAlgorithmManager, $compressionManager;

        $targetKey = $key ?? $jwk;
        $keyMgr = $keyEncryptionAlgorithmManager ?? new AlgorithmManager([new A256KW()]);
        $contentMgr = $contentEncryptionAlgorithmManager ?? new AlgorithmManager([new A256CBCHS512()]);
        $compMgr = $compressionManager ?? new CompressionMethodManager([]);

        $jweDecrypter = new JWEDecrypter(
            $keyMgr,
            $contentMgr,
            $compMgr
        );

        try {
            $jwe = (new CompactSerializer())->unserialize($token);
            if ($jweDecrypter->decryptUsingKey($jwe, $targetKey, 0)) {
                return $jwe->getPayload();
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
}
