<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Support;

use Bespredel\EncryptionForm\Exceptions\MissingResourceException;
use Illuminate\Support\Facades\Cache;

class Render
{
    /**
     * CSS public path.
     */
    private const CSS_PUBLIC_PATH = 'vendor/encryption-form/css/form-encrypt.min.css';

    /**
     * JavaScript encrypt public path.
     */
    private const JSENCRYPT_PUBLIC_PATH = 'vendor/encryption-form/js/jsencrypt.min.js';

    /**
     * Form encrypt public path.
     */
    private const FORM_ENCRYPT_PUBLIC_PATH = 'vendor/encryption-form/js/form-encrypt.min.js';

    /**
     * Cache TTL seconds.
     */
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * Render styles
     *
     * @return string HTML code for the styles
     *
     * @throws MissingResourceException
     */
    public static function styles(): string
    {
        if (!encryption_form_enabled()) {
            return '';
        }

        $formEncryptStylePath = public_path(self::CSS_PUBLIC_PATH);
        self::ensureResourceExists($formEncryptStylePath, 'CSS');

        $formEncryptStyleSri = self::resolveSri($formEncryptStylePath, 'form_encrypt_css_sri');

        return '<link rel="stylesheet" href="/' . self::CSS_PUBLIC_PATH . '" integrity="sha384-'
            . $formEncryptStyleSri . '" crossorigin="anonymous">';
    }

    /**
     * Render scripts
     *
     * @return string HTML code for the scripts
     *
     * @throws MissingResourceException
     */
    public static function scripts(): string
    {
        if (!encryption_form_enabled()) {
            return '';
        }

        $jsEncryptPath = public_path(self::JSENCRYPT_PUBLIC_PATH);
        $formEncryptPath = public_path(self::FORM_ENCRYPT_PUBLIC_PATH);

        self::ensureResourceExists($jsEncryptPath, 'JavaScript');
        self::ensureResourceExists($formEncryptPath, 'JavaScript');

        $jsEncryptSri = self::resolveSri($jsEncryptPath, 'jsencrypt_js_sri');
        $formEncryptSri = self::resolveSri($formEncryptPath, 'form_encrypt_js_sri');

        $payload = self::buildClientPayload();
        $publicKey = $payload['public_key'];
        $prefix = $payload['prefix'];
        $translations = $payload['translations'];
        $jsEncryptPublicPath = self::JSENCRYPT_PUBLIC_PATH;
        $formEncryptPublicPath = self::FORM_ENCRYPT_PUBLIC_PATH;

        return <<<HTML
<script src="/{$jsEncryptPublicPath}" integrity="sha384-{$jsEncryptSri}" crossorigin="anonymous"></script>
<script src="/{$formEncryptPublicPath}" integrity="sha384-{$formEncryptSri}" crossorigin="anonymous"></script>
<script>
    window.ENCRYPTION_FORM = {
        'public_key': `{$publicKey}`,
        'prefix': `{$prefix}`,
        trans: (str, params = {}) => {
            let translation_dict = {$translations};
            let translation = translation_dict[str] || str;
            for (const key in params) {
                translation = translation.replace(new RegExp(`:` + key, 'g'), params[key]);
            }
            return translation;
        }
    }
</script>
HTML;
    }

    /**
     * Ensure the resource exists.
     *
     * @param string $path         Path to the resource
     * @param string $resourceType Type of the resource
     *
     * @return void
     *
     * @throws MissingResourceException
     */
    private static function ensureResourceExists(string $path, string $resourceType): void
    {
        if (!file_exists($path)) {
            throw new MissingResourceException("Required {$resourceType} files are missing. Please run: php artisan vendor:publish --tag=encryption-form");
        }
    }

    /**
     * Resolve the SRIs.
     *
     * @param string $path           Path to the resource
     * @param string $cacheKeySuffix Suffix for the cache key
     *
     * @return string SRI for the resource
     */
    private static function resolveSri(string $path, string $cacheKeySuffix): string
    {
        $lastModified = filemtime($path) ?: time();

        return Cache::remember(
            'encryption-form:' . $cacheKeySuffix . '_' . $lastModified,
            self::CACHE_TTL_SECONDS,
            static fn() => base64_encode(hash_file('sha384', $path, true))
        );
    }

    /**
     * Build the client payload.
     *
     * @return array Client payload
     */
    private static function buildClientPayload(): array
    {
        $publicKey = (string)config('encryption-form.public_key', '');
        $prefix = (string)config('encryption-form.prefix', 'ENCF:');
        $translations = json_encode(trans('encryption-form::encryption-form'));

        return [
            'public_key'   => addslashes($publicKey),
            'prefix'       => addslashes($prefix),
            'translations' => $translations !== false ? $translations : '{}',
        ];
    }
}
