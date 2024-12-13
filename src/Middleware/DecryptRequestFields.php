<?php

namespace Bespredel\EncryptionForm\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class DecryptRequestFields
{
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
            Log::error('Private key not found');
            return $next($request);
        }

        // Decrypt all encrypted request fields
        $decrypted = collect($request->all())->mapWithKeys(function ($value, $key) use ($privateKey) {
            if (is_string($value) && str_starts_with($value, 'ENCF:')) {
                return [$key => $this->decryptField($value, $privateKey)];
            }
            return [$key => $value];
        })->toArray();

        $request->merge($decrypted);

        return $next($request);
    }

    /**
     * Decrypt field
     *
     * @param string $value
     * @param string $privateKey
     *
     * @return string|null
     */
    protected function decryptField(string $value, string $privateKey): ?string
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
}