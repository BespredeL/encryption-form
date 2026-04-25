<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Middleware;

use Bespredel\EncryptionForm\Services\Contracts\DecryptorInterface;
use Closure;
use Bespredel\EncryptionForm\Exceptions\DecryptionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DecryptRequestFields
{
    /**
     * System fields that should not be decrypted
     *
     * @var array System fields that should not be decrypted
     */
    protected array $excludedFields = [
        '_token',
        '_method',
        '_previous',
        '_flash',
    ];

    /**
     * Strict mode configuration key
     */
    private const STRICT_MODE_CONFIG_KEY = 'encryption-form.strict_mode';

    /**
     * Request decryptor
     *
     * @var DecryptorInterface
     */
    protected DecryptorInterface $decryptor;

    /**
     * Constructor
     *
     * @param DecryptorInterface $decryptor Request decryptor
     */
    public function __construct(DecryptorInterface $decryptor)
    {
        $this->decryptor = $decryptor;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request Incoming request
     * @param Closure $next    Next middleware
     *
     * @return mixed
     * @throws DecryptionException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!encryption_form_enabled()) {
            return $next($request);
        }

        $privateKey = config('encryption-form.private_key');
        if (!$privateKey) {
            Log::warning('Private key for encryption form is not set');
            return $next($request);
        }

        $fieldPrefix = config('encryption-form.prefix', 'ENCF:');

        $requestData = $request->except($this->excludedFields);
        $encryptedFieldCount = $this->countEncryptedFields($requestData, $fieldPrefix);

        $decrypted = $this->decryptor->decryptValues($requestData, $privateKey, $fieldPrefix);
        $failedFieldCount = $this->countFailedDecryptions($requestData, $decrypted, $fieldPrefix);

        if ($failedFieldCount > 0) {
            Log::warning('Encryption form request decryption completed with failures', [
                'service'               => 'encryption-form.middleware',
                'encrypted_field_count' => $encryptedFieldCount,
                'failed_field_count'    => $failedFieldCount,
                'strict_mode'           => $this->isStrictModeEnabled(),
            ]);
        }

        if ($failedFieldCount > 0 && $this->isStrictModeEnabled()) {
            throw new DecryptionException('Failed to decrypt one or more encrypted request fields.');
        }

        $request->merge($decrypted);

        return $next($request);
    }

    /**
     * Count the number of encrypted fields in the request data.
     *
     * @param array  $requestData Request data
     * @param string $fieldPrefix Prefix for encrypted fields
     *
     * @return int Number of encrypted fields
     */
    private function countEncryptedFields(array $requestData, string $fieldPrefix): int
    {
        return collect($requestData)
            ->filter(static fn($value): bool => is_string($value) && str_starts_with($value, $fieldPrefix))
            ->count();
    }

    /**
     * Count the number of failed decryptions in the request data.
     *
     * @param array  $requestData Request data to decrypt
     * @param array  $decrypted   Decrypted data to compare with
     * @param string $fieldPrefix Prefix for encrypted fields
     *
     * @return int Number of failed decryptions
     */
    private function countFailedDecryptions(array $requestData, array $decrypted, string $fieldPrefix): int
    {
        return collect($requestData)
            ->filter(static fn($value): bool => is_string($value) && str_starts_with($value, $fieldPrefix))
            ->filter(function ($_value, $key) use ($decrypted): bool {
                return array_key_exists($key, $decrypted) && $decrypted[$key] === null;
            })
            ->count();
    }

    /**
     * Check if strict mode is enabled.
     *
     * @return bool True if strict mode is enabled, false otherwise
     */
    private function isStrictModeEnabled(): bool
    {
        return (bool)config(self::STRICT_MODE_CONFIG_KEY, false);
    }
}