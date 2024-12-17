<?php

namespace Bespredel\EncryptionForm\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Bespredel\EncryptionForm\Services\RequestDecryptor;

class DecryptRequestFields
{
    /**
     * Request decryptor
     *
     * @var RequestDecryptor
     */
    protected $decryptor;

    public function __construct(RequestDecryptor $decryptor)
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
        $privateKey = config('encryption_form.private_key');
        if (!$privateKey) {
            Log::warning('Private key for encryption form is not set');
            return $next($request);
        }

        $fieldPrefix = config('encryption_form.prefix', 'ENCF:');

        $decrypted = $this->decryptor->decryptFields($request->all(), $privateKey, $fieldPrefix);
        $request->merge($decrypted);

        return $next($request);
    }
}