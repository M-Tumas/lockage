<?php

declare(strict_types=1);

namespace Lockage\Lockage;

use Lockage\Lockage\Exceptions\NumberTooBigException;

/**
 * Class KeyPair
 * @package Lockage\Lockage
 */
class KeyPair
{
    private const MAX_INDEX = 9999;
    private const DEDUCTION = 10000;

    private const DATA_PATH = '/Data/data-last.txt';

    private Cryptography $cryptography;
    private array $data;

    /**
     * KeyPair constructor.
     * @param Cryptography $cryptography
     */
    public function __construct(Cryptography $cryptography)
    {
        $this->cryptography = $cryptography;
    }

    /**
     * @param int $randomCode
     * @param int $trailerCode
     * @param int $officeCode
     * @return mixed
     * @throws Exceptions\CryptographyDecryptionException
     * @throws NumberTooBigException
     * @throws \JsonException
     * @throws \SodiumException
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
     * @throws Exceptions\CryptographyDecryptionException
     * @throws \JsonException
     * @throws \SodiumException
     */
    private function loadData(): void
    {
        $this->data = json_decode($this->cryptography->decrypt(file_get_contents(__DIR__ . self::DATA_PATH)), true, 512, JSON_THROW_ON_ERROR);
    }
}
