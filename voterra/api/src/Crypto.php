<?php

namespace App;

final class Crypto
{
    private const string CIPHER = 'aes-256-gcm';
    private const int IV_BYTES = 12;
    private const int TAG_BYTES = 16;

    public const string AAD_SETUP_V1 = 'acm:setup:v1';
    public const string AAD_RESULTS_V1 = 'acm:results:v1';

    /**
     * Envelope format:
     *  { v: 1, alg: 'A256GCM', iv: b64, tag: b64, ct: b64 }
     */
    public static function isEnvelope(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return isset($value['v'], $value['alg'], $value['iv'], $value['tag'], $value['ct'])
            && (int)$value['v'] === 1
            && (string)$value['alg'] === 'A256GCM'
            && is_string($value['iv'])
            && is_string($value['tag'])
            && is_string($value['ct']);
    }

    public static function encryptJson(array $payload, string $aad): array
    {
        $json = json_encode($payload);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode JSON');
        }

        return self::encryptString($json, $aad);
    }

    public static function decryptEnvelopeToArray(array $envelope, string $aad): array
    {
        $plaintext = self::decryptEnvelopeToString($envelope, $aad);
        $decoded = json_decode($plaintext, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Decrypted payload is not valid JSON');
        }

        return $decoded;
    }

    public static function encryptString(string $plaintext, string $aad): array
    {
        $key = self::getKey();
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $ct = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            self::TAG_BYTES
        );

        if ($ct === false || $tag === '') {
            throw new \RuntimeException('Encryption failed');
        }

        return [
            'v' => 1,
            'alg' => 'A256GCM',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ct)
        ];
    }

    public static function decryptEnvelopeToString(array $envelope, string $aad): string
    {
        if (!self::isEnvelope($envelope)) {
            throw new \RuntimeException('Invalid envelope');
        }

        $iv = base64_decode((string)$envelope['iv'], true);
        $tag = base64_decode((string)$envelope['tag'], true);
        $ct = base64_decode((string)$envelope['ct'], true);

        if ($iv === false || $tag === false || $ct === false) {
            throw new \RuntimeException('Invalid base64 fields');
        }

        if (strlen($iv) !== self::IV_BYTES || strlen($tag) !== self::TAG_BYTES) {
            throw new \RuntimeException('Invalid IV/tag length');
        }

        $key = self::getKey();
        $pt = openssl_decrypt(
            $ct,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($pt === false) {
            throw new \RuntimeException('Decryption failed (tampered or wrong key)');
        }

        return $pt;
    }

    private static function getKey(): string
    {
        $b64 = getenv('ACM_CRYPTO_KEY_B64');
        if (is_string($b64) && $b64 !== '') {
            $key = base64_decode($b64, true);
            if ($key === false || strlen($key) !== 32) {
                throw new \RuntimeException('ACM_CRYPTO_KEY_B64 must be base64 for 32 bytes');
            }
            return $key;
        }

        $pass = getenv('ACM_CRYPTO_KEY');
        if (is_string($pass) && $pass !== '') {
            return hash('sha256', $pass, true);
        }

        throw new \RuntimeException('Missing ACM_CRYPTO_KEY_B64 (recommended) or ACM_CRYPTO_KEY');
    }
}
