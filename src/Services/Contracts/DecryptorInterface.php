<?php

namespace Bespredel\EncryptionForm\Services\Contracts;

interface DecryptorInterface
{
    /**
     * Decrypt an encrypted field
     *
     * @param string $value
     * @param string $privateKey
     * @param string $fieldPrefix
     *
     * @return string|null
     */
    public function decryptValue(string $value, string $privateKey, string $fieldPrefix): ?string;

    /**
     * Decrypt multiple values in an array
     *
     * @param array  $fields
     * @param string $privateKey
     * @param string $fieldPrefix
     *
     * @return array
     */
    public function decryptValues(array $fields, string $privateKey, string $fieldPrefix): array;
}