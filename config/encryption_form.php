<?php

/**
 * @see https://github.com/bespredel/encryption-form
 */

return [
    'public_key'   => env('ENCRYPTION_FORM_PUBLIC_KEY'),
    'private_key'  => env('ENCRYPTION_FORM_PUBLIC_KEY'),
    'prefix'       => env('ENCRYPTION_FORM_PREFIX', 'ENCF:'),
    'key_rotation' => [
        'enabled'         => env('ENCRYPTION_FORM_KEY_ROTATION_ENABLED', false),
        'cron_expression' => '0 0 * * *',
    ],
];