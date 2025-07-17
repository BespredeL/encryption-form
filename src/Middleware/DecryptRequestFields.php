<?php

namespace Bespredel\EncryptionForm\Middleware;

use Bespredel\EncryptionForm\Services\Contracts\DecryptorInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class  DecryptRequestFields
{
    /**
     * Request decryptor
     *
     * @var DecryptorInterface
     */
    protected DecryptorInterface $decryptor;

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
        if (!config('encryption-form.enabled', true)) {
            return $next($request);
        }

        $privateKey = config('encryption-form.private_key');
        if (!$privateKey) {
            Log::warning('Private key for encryption form is not set');
            return $next($request);
        }

        $fieldPrefix = config('encryption-form.prefix', 'ENCF:');
        $decrypted = $this->decryptor->decryptValues($request->all(), $privateKey, $fieldPrefix);
        $request->merge($decrypted);

        return $next($request);
    }
}