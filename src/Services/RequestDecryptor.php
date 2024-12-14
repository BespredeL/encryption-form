<?php

namespace Bespredel\EncryptionForm\Services;

use Illuminate\Support\Facades\Log;

class RequestDecryptor
{
    /**
     * Decrypt an encrypted field
     *
     * @param string $value      Encrypted value
     * @param string $privateKey Private key for decryption
     *
     * @return string|null
     */
    public function decryptValue(string $value, string $privateKey): ?string
    {
        $res = openssl_pkey_get_private($privateKey);
        if (!$res) {
            Log::warning('Error parsing private key');
            return null;
        }

        $decodedValue = base64_decode((string)str($value)->after('ENCF:'), true);
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
     * @param array  $fields     Fields to decrypt
     * @param string $privateKey Private key for decryption
     *
     * @return array
     */
    public function decryptFields(array $fields, string $privateKey): array
    {
        return collect($fields)->mapWithKeys(function ($value, $key) use ($privateKey) {
            if (is_string($value) && str_starts_with($value, 'ENCF:')) {
                return [$key => $this->decryptValue($value, $privateKey)];
            }
            return [$key => $value];
        })->toArray();
    }
}