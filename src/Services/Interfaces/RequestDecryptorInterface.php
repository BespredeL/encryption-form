<?php

namespace Bespredel\EncryptionForm\Services\Interfaces;

interface RequestDecryptorInterface
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
     * Decrypt multiple fields in an array
     *
     * @param array  $fields
     * @param string $privateKey
     * @param string $fieldPrefix
     *
     * @return array
     */
    public function decryptFields(array $fields, string $privateKey, string $fieldPrefix): array;
}