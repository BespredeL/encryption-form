<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Middleware;

use Bespredel\EncryptionForm\Services\Contracts\DecryptorInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DecryptRequestFields
{
    /**
     * System fields that should not be decrypted
     *
     * @var array<string>
     */
    protected array $excludedFields = [
        '_token',
        '_method',
        '_previous',
        '_flash',
    ];

    /**
     * Request decryptor
     *
     * @var DecryptorInterface
     */
    protected DecryptorInterface $decryptor;

    /**
     * @param DecryptorInterface $decryptor
     */
    public function __construct(DecryptorInterface $decryptor)
    {
        $this->decryptor = $decryptor;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
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

        // Get all request data excluding system fields
        $requestData = $request->except($this->excludedFields);

        // Decrypt only non-system fields
        $decrypted = $this->decryptor->decryptValues($requestData, $privateKey, $fieldPrefix);

        // Merge decrypted values back, preserving system fields
        $request->merge($decrypted);

        return $next($request);
    }
}