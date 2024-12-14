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
        Blade::directive('encryptFormScripts', function () {
            $publicKey = config('encryption_form.public_key');

            if (!$publicKey) {
                throw new \Exception('Public key for encryption is not set in the configuration.');
            }

            $escapedKey = addslashes($publicKey);
            $translations = json_encode(trans('encryption_form'));

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
    window.ENCRYPTION_FORM_PUBLIC_KEY = `{$escapedKey}`;
    window.ENCRYPTION_FORM_LANG = {$translations};
</script>
HTML;
        });
    }
}