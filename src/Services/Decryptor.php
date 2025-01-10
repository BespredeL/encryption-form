<?php

namespace Bespredel\EncryptionForm\Services;

use Bespredel\EncryptionForm\Services\Contracts\DecryptorInterface;
use Illuminate\Support\Facades\Log;

class Decryptor implements DecryptorInterface
{
    /**
     * Decrypt an encrypted field
     *
     * @param string $value       Encrypted value
     * @param string $privateKey  Private key for decryption
     * @param string $fieldPrefix Field prefix
     *
     * @return string|null
     */
    public function decryptValue(string $value, string $privateKey, string $fieldPrefix): ?string
    {
        $res = openssl_pkey_get_private($privateKey);
        if (!$res) {
            Log::warning('Error parsing private key');
            return null;
        }

        $decodedValue = base64_decode((string)str($value)->after($fieldPrefix), true);
        if ($decodedValue === false) {
            Log::warning('Failed to base64 decode value');
            return null;
        }

        $decrypted = '';
        if (!openssl_private_decrypt($decodedValue, $decrypted, $res)) {
            Log::warning('Decryption failed for value');
            return null;
        }

        return $decrypted;
    }

    /**
     * Decrypt multiple fields in an array
     *
     * @param array  $fields      Fields to decrypt
     * @param string $privateKey  Private key for decryption
     * @param string $fieldPrefix Field prefix
     *
     * @return array
     */
    public function decryptValues(array $fields, string $privateKey, string $fieldPrefix): array
    {
        return collect($fields)->mapWithKeys(function ($value, $key) use ($privateKey, $fieldPrefix) {
            if (is_string($value) && str_starts_with($value, $fieldPrefix)) {
                return [$key => $this->decryptValue($value, $privateKey, $fieldPrefix)];
            }
            return [$key => $value];
        })->toArray();
    }
}