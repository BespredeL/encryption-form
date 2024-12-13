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

            return <<<HTML
<script src="/vendor/encryption-form/js/jsencrypt.min.js"></script>
<script src="/vendor/encryption-form/js/form-encrypt.js"></script>
<script>
    window.ENCRYPTION_FORM_PUBLIC_KEY = `{$escapedKey}`;
    window.ENCRYPTION_FORM_LANG = {$translations};
</script>
HTML;
        });
    }
}
