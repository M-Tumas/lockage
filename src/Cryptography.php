<?php

declare(strict_types=1);

namespace Lockage\Lockage;

use Lockage\Lockage\Exceptions\CryptographyDecryptionException;
use SodiumException;

/**
 * Class Cryptography
 * @package Lockage\Lockage
 */
class Cryptography
{
    private const NONCE_LENGTH = 32;

    private string $key;

    public function __construct()
    {
        $this->key = base64_decode(config('lockage.cryptography.key'));
    }

    /**
     * @param string $message
     * @param string $additionalData
     * @return string
     * @throws SodiumException
     * @throws \Exception
     */
    public function encrypt(string $message, string $additionalData = ''): string
    {
        $nonce = $this->generateNonce();

        $encrypted = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($message, $additionalData, $nonce, $this->key);

        return base64_encode($nonce . $encrypted);
    }

    /**
     * @param string $ciphertext
     * @param string $additionalData
     * @return string
     * @throws CryptographyDecryptionException
     * @throws SodiumException
     */
    public function decrypt(string $ciphertext, string $additionalData = ''): string
    {
        $this->validateLength($ciphertext);
        $nonce = base64_decode(substr($ciphertext, 0, self::NONCE_LENGTH));
        $ciphertext = base64_decode(substr($ciphertext, self::NONCE_LENGTH));

        $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $additionalData, $nonce, $this->key);

        if (!$decrypted) {
            throw new CryptographyDecryptionException('Failed to decrypt');
        }

        return $decrypted;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function generateNonce(): string
    {
        return random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
    }

    /**
     * @param string $string
     * @throws CryptographyDecryptionException
     */
    private function validateLength(string $string): void
    {
        if (strlen($string) < self::NONCE_LENGTH) {
            throw new CryptographyDecryptionException('Invalid length');
        }
    }
}
