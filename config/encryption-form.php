<?php

/**
 * Encryption Form configuration file.
 *
 * This file is used to configure the encryption form package.
 * For more information, see the documentation:
 * @see https://github.com/bespredel/encryption-form
 */

return [
    // Enable or disable the encryption form functionality
    'enabled'      => env('ENCRYPTION_FORM_ENABLED', true),

    // Public key used for encrypting form data (should be set in .env)
    'public_key'   => env('ENCRYPTION_FORM_PUBLIC_KEY'),

    // Private key used for decrypting form data (should be set in .env, keep it secret!)
    'private_key'  => env('ENCRYPTION_FORM_PRIVATE_KEY'),

    // Prefix for encrypted fields (used to identify encrypted data)
    'prefix'       => env('ENCRYPTION_FORM_PREFIX', 'ENCF:'),

    // Key rotation settings
    'key_rotation' => [
        // Enable or disable automatic key rotation
        'enabled'         => env('ENCRYPTION_FORM_KEY_ROTATION_ENABLED', false),

        // Cron expression for scheduling key rotation (default: daily at midnight)
        'cron_expression' => '0 0 * * *',
    ],
];