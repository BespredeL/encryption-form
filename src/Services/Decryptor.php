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
        if (empty($value)) {
            $this->logFailure('empty_value');
            return null;
        }

        if (empty($privateKey)) {
            $this->logFailure('empty_private_key');
            return null;
        }

        if (!str_starts_with($value, $encDataPrefix)) {
            $this->logFailure('prefix_mismatch', [
                'prefix'        => $encDataPrefix,
                'value_preview' => substr($value, 0, 50),
            ], 'debug');
            return null;
        }

        $res = openssl_pkey_get_private($privateKey);
        if (!$res) {
            $error = openssl_error_string();
            $this->logFailure('invalid_private_key', [
                'error' => $error,
            ]);
            return null;
        }

        $decodedValue = base64_decode((string)str($value)->after($encDataPrefix), true);
        if ($decodedValue === false) {
            $this->logFailure('invalid_base64_payload', [
                'value_preview' => substr($value, 0, 50),
            ]);
            return null;
        }

        $decrypted = '';
        if (!openssl_private_decrypt($decodedValue, $decrypted, $res)) {
            $error = openssl_error_string();
            $this->logFailure('openssl_decrypt_failed', [
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
            if (is_string($value) && str_starts_with($value, $encDataPrefix)) {
                $decrypted = $this->decryptValue($value, $privateKey, $encDataPrefix);

                if ($decrypted === null) {
                    $this->logFailure('field_decryption_failed', [
                        'field' => (string)$key,
                    ]);
                }

                return [$key => $decrypted];
            }
            return [$key => $value];
        })->toArray();
    }

    /**
     * Log a decryption failure.
     *
     * @param string $reason  Reason for the failure
     * @param array  $context Context for the failure
     * @param string $level   Level of the failure
     *
     * @return void
     */
    private function logFailure(string $reason, array $context = [], string $level = 'warning'): void
    {
        $payload = array_merge([
            'reason'  => $reason,
            'service' => 'encryption-form.decryptor',
        ], $context);

        if ($level === 'debug') {
            Log::debug('Encryption form decryption diagnostics', $payload);
            return;
        }

        Log::warning('Encryption form decryption diagnostics', $payload);
    }
}