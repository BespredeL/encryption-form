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

            return <<<HTML
<script src="/vendor/encryption-form/js/jsencrypt.min.js"></script>
<script src="/vendor/encryption-form/js/form-encrypt.js"></script>
<script>
    window.ENCRYPTION_FORM_PUBLIC_KEY = `{$escapedKey}`;
    window.ENCRYPTION_FORM_LANG = {
        'Encryption is not available. Do you want to submit the form without encryption?': 'Encryption is not available. Do you want to submit the form without encryption?',
        'Form submission canceled by user.': 'Form submission canceled by user.',
        'Form encrypted successfully.': 'Form encrypted successfully.',
        'Failed to encrypt form.': 'Failed to encrypt form.',
        'Encryption not available.': 'Encryption not available.'
    };
</script>
HTML;
        });
    }
}
