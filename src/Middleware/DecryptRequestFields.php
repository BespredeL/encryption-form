<?php

namespace Bespredel\EncryptionForm\Middleware;

use Closure;
use Illuminate\Http\Request;
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
            return $next($request);
        }

        $decrypted = $this->decryptor->decryptFields($request->all(), $privateKey);
        $request->merge($decrypted);

        return $next($request);
    }
}