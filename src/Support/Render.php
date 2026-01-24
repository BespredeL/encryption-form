<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Support;

use Bespredel\EncryptionForm\Exceptions\MissingResourceException;
use Illuminate\Support\Facades\Cache;

class Render
{
    /**
     * Render styles
     *
     * @return string
     *
     * @throws MissingResourceException
     */
    public static function styles(): string
    {
        if (!encryption_form_enabled()) {
            return '';
        }

        $formEncryptStylePath = public_path('vendor/encryption-form/css/form-encrypt.min.css');

        if (!file_exists($formEncryptStylePath)) {
            throw new MissingResourceException('Required CSS files are missing. Please run: php artisan vendor:publish --tag=encryption-form');
        }

        $lastModified = filemtime($formEncryptStylePath);
        $formEncryptStyleSri = Cache::remember(
            'encryption-form:form_encrypt_css_sri_' . $lastModified,
            3600,
            fn() => base64_encode(hash_file('sha384', $formEncryptStylePath, true))
        );

        return '<link rel="stylesheet" href="/vendor/encryption-form/css/form-encrypt.min.css" integrity="sha384-'
            . $formEncryptStyleSri . '" crossorigin="anonymous">';
    }

    /**
     * Render scripts
     *
     * @return string
     *
     * @throws MissingResourceException
     */
    public static function scripts(): string
    {
        if (!encryption_form_enabled()) {
            return '';
        }

        $jsEncryptPath = public_path('vendor/encryption-form/js/jsencrypt.min.js');
        $formEncryptPath = public_path('vendor/encryption-form/js/form-encrypt.min.js');

        if (!file_exists($jsEncryptPath) || !file_exists($formEncryptPath)) {
            throw new MissingResourceException('Required JavaScript files are missing. Please run: php artisan vendor:publish --tag=encryption-form');
        }

        $jsEncryptLastModified = filemtime($jsEncryptPath);
        $formEncryptLastModified = filemtime($formEncryptPath);

        $jsEncryptSri = Cache::remember(
            'encryption-form:jsencrypt_js_sri_' . $jsEncryptLastModified,
            3600,
            fn() => base64_encode(hash_file('sha384', $jsEncryptPath, true))
        );

        $formEncryptSri = Cache::remember(
            'encryption-form:form_encrypt_js_sri_' . $formEncryptLastModified,
            3600,
            fn() => base64_encode(hash_file('sha384', $formEncryptPath, true))
        );

        $publicKey = config('encryption-form.public_key');
        $prefix = config('encryption-form.prefix', 'ENCF:');
        $escapedKey = addslashes($publicKey);
        $translations = json_encode(trans('encryption-form::encryption-form'));

        return <<<HTML
<script src="/vendor/encryption-form/js/jsencrypt.min.js" integrity="sha384-{$jsEncryptSri}" crossorigin="anonymous"></script>
<script src="/vendor/encryption-form/js/form-encrypt.min.js" integrity="sha384-{$formEncryptSri}" crossorigin="anonymous"></script>
<script>
    window.ENCRYPTION_FORM = {
        'public_key': `{$escapedKey}`,
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
}
