<?php

declare(strict_types=1);

namespace Mtu\Lockage;

use JsonException;
use Lockage\Lockage\Exceptions\CryptographyDecryptionException;
use Lockage\Lockage\Exceptions\NumberTooBigException;
use Mtu\Lockage\Exceptions\EncryptedKeyFileDoesNotExistsException;
use SodiumException;

class KeyPair
{
    private const MAX_INDEX = 9999;
    private const DEDUCTION = 10000;

    private Cryptography $cryptography;
    private string $dataFilePath;
    private array $data;

    /**
     * KeyPair constructor.
     * @param Cryptography $cryptography
     */
    public function __construct(Cryptography $cryptography)
    {
        $this->dataFilePath = config('lockage.encrypted_file_path');
        $this->cryptography = $cryptography;
    }

    /**
     * @param int $randomCode
     * @param int $trailerCode
     * @param int $officeCode
     * @return mixed
     * @throws Exceptions\CryptographyDecryptionException
     * @throws NumberTooBigException
     * @throws JsonException
     * @throws SodiumException
     */
    public function getPair(int $randomCode, int $trailerCode, int $officeCode)
    {
        $this->validateNumbers($randomCode, $trailerCode, $officeCode);
        $this->loadData();

        $key = $this->getKey($randomCode, $trailerCode, $officeCode);

        return $this->data[$key];
    }

    /**
     * @param ...$numbers
     * @return int
     */
    private function getKey(...$numbers): int
    {
        $key = 0;
        foreach ($numbers as $number) {
            $key += $number;
            if ($key > self::MAX_INDEX) {
                $key -= self::DEDUCTION;
            }
        }

        return $key;
    }

    /**
     * @param ...$numbers
     * @throws NumberTooBigException
     */
    private function validateNumbers(...$numbers): void
    {
        foreach ($numbers as $number) {
            if ($number > self::MAX_INDEX) {
                throw new NumberTooBigException('Number too big: ' . $number);
            }
        }
    }

    /**
     * @throws EncryptedKeyFileDoesNotExistsException
     * @throws JsonException
     * @throws CryptographyDecryptionException
     * @throws SodiumException
     */
    private function loadData(): void
    {
        $this->validateDataFile();
        $this->data = json_decode($this->cryptography->decrypt(file_get_contents($this->dataFilePath)), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws EncryptedKeyFileDoesNotExistsException
     */
    private function validateDataFile(): void
    {
        if (!file_exists($this->dataFilePath)) {
            throw new EncryptedKeyFileDoesNotExistsException('File does not exists');
        }
    }
}
