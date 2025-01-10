<?php

namespace Bespredel\EncryptionForm\Blade;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;

class Directives
{
    /**
     * Registering Blade Directives
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function register(): void
    {
        // Styles injection
        Blade::directive('encryptFormStyles', function () {
            if (!config('encryption-form.enabled', true)) {
                return '';
            }

            $formEncryptStylePath = public_path('vendor/encryption-form/css/form-encrypt.min.css');

            if (!file_exists($formEncryptStylePath)) {
                throw new \RuntimeException('Required CSS files are missing.');
            }

            $lastModified = filemtime($formEncryptStylePath);
            $formEncryptStyleSri = Cache::remember('encryption-form:form_encrypt_css_sri_' . $lastModified, 3600, function () use ($formEncryptStylePath) {
                return base64_encode(hash_file('sha384', $formEncryptStylePath, true));
            });

            return '<link rel="stylesheet" href="/vendor/encryption-form/css/form-encrypt.min.css" integrity="sha384-' . $formEncryptStyleSri . '" crossorigin="anonymous">';
        });

        // Scripts injection
        Blade::directive('encryptFormScripts', function () {
            if (!config('encryption-form.enabled', true)) {
                return '';
            }

            $jsEncryptPath = public_path('vendor/encryption-form/js/jsencrypt.min.js');
            $formEncryptPath = public_path('vendor/encryption-form/js/form-encrypt.min.js');

            if (!file_exists($jsEncryptPath) || !file_exists($formEncryptPath)) {
                throw new \RuntimeException('Required JavaScript files are missing.');
            }

            $jsEncryptLastModified = filemtime($jsEncryptPath);
            $formEncryptLastModified = filemtime($formEncryptPath);

            $jsEncryptSri = Cache::remember('encryption-form:jsencrypt_js_sri_' . $jsEncryptLastModified, 3600, function () use ($jsEncryptPath) {
                return base64_encode(hash_file('sha384', $jsEncryptPath, true));
            });

            $formEncryptSri = Cache::remember('encryption-form:form_encrypt_js_sri_' . $formEncryptLastModified, 3600, function () use ($formEncryptPath) {
                return base64_encode(hash_file('sha384', $formEncryptPath, true));
            });

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
        'prefix': `$prefix`,
        trans: (str, params = {}) => {
            let translation_dict = $translations;
            let translation = translation_dict[str] || str;
            for (const key in params) {
                translation = translation.replace(new RegExp(`:` + key, 'g'), params[key]);
            }
            return translation;
        }
    }
</script>
HTML;
        });
    }
}