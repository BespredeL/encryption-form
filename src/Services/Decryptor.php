<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Services;

use Bespredel\EncryptionForm\Services\Contracts\DecryptorInterface;
use Illuminate\Support\Facades\Log;

class Decryptor implements DecryptorInterface
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
    public function decryptValue(string $value, string $privateKey, string $encDataPrefix): ?string
    {
        // Validate input
        if (empty($value)) {
            Log::warning('Decryption attempted with empty value');
            return null;
        }

        if (empty($privateKey)) {
            Log::warning('Decryption attempted with empty private key');
            return null;
        }

        // Validate prefix
        if (!str_starts_with($value, $encDataPrefix)) {
            Log::debug('Value does not start with encryption prefix', [
                'prefix'        => $encDataPrefix,
                'value_preview' => substr($value, 0, 50),
            ]);
            return null;
        }

        $res = openssl_pkey_get_private($privateKey);
        if (!$res) {
            $error = openssl_error_string();
            Log::warning('Error parsing private key', [
                'error' => $error,
            ]);
            return null;
        }

        $decodedValue = base64_decode((string)str($value)->after($encDataPrefix), true);
        if ($decodedValue === false) {
            Log::warning('Failed to base64 decode value', [
                'value_preview' => substr($value, 0, 50),
            ]);
            return null;
        }

        $decrypted = '';
        if (!openssl_private_decrypt($decodedValue, $decrypted, $res)) {
            $error = openssl_error_string();
            Log::warning('Decryption failed for value', [
                'error'         => $error,
                'value_preview' => substr($value, 0, 50),
            ]);
            return null;
        }

        return $decrypted;
    }

    /**
     * Decrypt multiple fields in an array
     *
     * @param array  $fields        Fields to decrypt
     * @param string $privateKey    Private key for decryption
     * @param string $encDataPrefix Encrypted data prefix
     *
     * @return array
     */
    public function decryptValues(array $fields, string $privateKey, string $encDataPrefix): array
    {
        return collect($fields)->mapWithKeys(function ($value, $key) use ($privateKey, $encDataPrefix) {
            // Only process string values that start with the encryption prefix
            if (is_string($value) && str_starts_with($value, $encDataPrefix)) {
                return [$key => $this->decryptValue($value, $privateKey, $encDataPrefix)];
            }
            return [$key => $value];
        })->toArray();
    }
}