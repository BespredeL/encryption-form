<?php

namespace Bespredel\EncryptionForm\Blade;

use Illuminate\Support\Facades\Blade;

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
        Blade::directive('encryptFormStyles', function () {
            if (!config('encryption-form.enabled', true)) {
                return '';
            }

            return '<link rel="stylesheet" href="/vendor/encryption-form/css/form-encrypt.css">';
        });

        Blade::directive('encryptFormScripts', function () {
            if (!config('encryption-form.enabled', true)) {
                return '';
            }

            $publicKey = config('encryption-form.public_key');
            $prefix = config('encryption-form.prefix', 'ENCF:');

            if (!$publicKey) {
                throw new \Exception('Public key for encryption is not set in the configuration.');
            }

            $escapedKey = addslashes($publicKey);
            $translations = json_encode(trans('encryption-form::encryption-form'));

            // Generate SRI hashes dynamically
            $jsencryptPath = public_path('vendor/encryption-form/js/jsencrypt.min.js');
            $formEncryptPath = public_path('vendor/encryption-form/js/form-encrypt.js');

            if (!file_exists($jsencryptPath) || !file_exists($formEncryptPath)) {
                throw new \Exception('Required JavaScript files are missing.');
            }

            $jsencryptSri = base64_encode(hash_file('sha384', $jsencryptPath, true));
            $formEncryptSri = base64_encode(hash_file('sha384', $formEncryptPath, true));

            return <<<HTML
<script src="/vendor/encryption-form/js/jsencrypt.min.js" integrity="sha384-{$jsencryptSri}" crossorigin="anonymous"></script>
<script src="/vendor/encryption-form/js/form-encrypt.js" integrity="sha384-{$formEncryptSri}" crossorigin="anonymous"></script>
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