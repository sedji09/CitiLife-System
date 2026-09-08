<?php
/**
 * CitiLife System - Security & Encryption Configuration
 */

if (file_exists(__DIR__ . '/../env.php')) {
    require_once __DIR__ . '/../env.php';
}

if (!defined('CHAT_CIPHER_METHOD')) {
    define('CHAT_CIPHER_METHOD', 'aes-256-cbc');
}

if (!defined('CHAT_ENCRYPTION_KEY')) {
    $rawKey = getenv('APP_ENCRYPTION_KEY') ?: ($_ENV['APP_ENCRYPTION_KEY'] ?? 'CitiLife-Medical-System-Chat-Secret-Key-2026-AES256');
    // Ensure raw 32-byte binary key for AES-256
    define('CHAT_ENCRYPTION_KEY', hash('sha256', $rawKey, true));
}

if (!function_exists('encryptMessage')) {
    /**
     * Encrypt a message string using AES-256-CBC
     * Output format: ENC:<base64(16_byte_iv + ciphertext)>
     *
     * @param string|null $plaintext
     * @return string
     */
    function encryptMessage(?string $plaintext): string
    {
        if ($plaintext === null || $plaintext === '') {
            return '';
        }

        // Generate a cryptographically secure 16-byte IV
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $plaintext,
            CHAT_CIPHER_METHOD,
            CHAT_ENCRYPTION_KEY,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            return $plaintext;
        }

        return 'ENC:' . base64_encode($iv . $encrypted);
    }
}

if (!function_exists('decryptMessage')) {
    /**
     * Decrypt a message string previously encrypted with encryptMessage()
     * Automatically returns original text if not encrypted or decryption fails.
     *
     * @param string|null $data
     * @return string
     */
    function decryptMessage(?string $data): string
    {
        if ($data === null || $data === '') {
            return '';
        }

        // If not prefixed with ENC:, it is legacy/plain text
        if (substr($data, 0, 4) !== 'ENC:') {
            return $data;
        }

        $raw = base64_decode(substr($data, 4), true);
        if ($raw === false || strlen($raw) < 17) {
            // Decoding failed or too short, return original
            return $data;
        }

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);

        $decrypted = openssl_decrypt(
            $ciphertext,
            CHAT_CIPHER_METHOD,
            CHAT_ENCRYPTION_KEY,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            return $data;
        }

        return $decrypted;
    }
}
