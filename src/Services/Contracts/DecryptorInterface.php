<?php

namespace Bespredel\EncryptionForm\Services\Contracts;

interface DecryptorInterface
{
    /**
     * Decrypt an encrypted field
     *
     * @param string $value         Encrypted value
     * @param string $privateKey    Private key for decryption
     * @param string $encDataPrefix Encrypted data prefix
     *
     * @return string|null
     */
    public function decryptValue(string $value, string $privateKey, string $encDataPrefix): ?string;

    /**
     * Decrypt multiple values in an array
     *
     * @param array  $fields        Fields to decrypt
     * @param string $privateKey    Private key for decryption
     * @param string $encDataPrefix Encrypted data prefix
     *
     * @return array
     */
    public function decryptValues(array $fields, string $privateKey, string $encDataPrefix): array;
}