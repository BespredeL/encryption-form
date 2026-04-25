<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Console\Commands;

use Bespredel\EncryptionForm\Support\EnvEditor;
use Illuminate\Console\Command;

class GenerateEncryptionKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string Signature of the console command
     */
    protected $signature = 'encryption-form:generate-keys';

    /**
     * The console command description.
     *
     * @var string Description of the console command
     */
    protected $description = 'Create a new RSA key pair to encrypt the forms.';

    /**
     * Env editor instance
     *
     * @var EnvEditor
     */
    private EnvEditor $envEditor;

    /**
     * Constructor
     *
     * @param EnvEditor $envEditor
     */
    public function __construct(EnvEditor $envEditor)
    {
        parent::__construct();
        $this->envEditor = $envEditor;
    }

    /**
     * Execute the console command.
     *
     * @return int Exit code
     */
    public function handle(): int
    {
        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($keyPair === false) {
            $this->error('Failed to generate RSA key pair. Please check OpenSSL configuration.');
            return self::FAILURE;
        }

        $privateKey = '';
        if (!openssl_pkey_export($keyPair, $privateKey)) {
            $this->error('Failed to export private key from key pair.');
            return self::FAILURE;
        }

        $keyDetails = openssl_pkey_get_details($keyPair);
        if ($keyDetails === false) {
            $this->error('Failed to get public key from key pair.');
            return self::FAILURE;
        }

        $publicKey = $keyDetails['key'];

        if (!$this->saveKeysToEnv($privateKey, $publicKey)) {
            return self::FAILURE;
        }

        $this->info('New RSA key pair generated and saved to .env');
        return self::SUCCESS;
    }

    /**
     * Saving keys to a .env file
     *
     * @param string $privateKey Private key
     * @param string $publicKey  Public key
     *
     * @return bool True if keys were saved successfully, false otherwise
     */
    protected function saveKeysToEnv(string $privateKey, string $publicKey): bool
    {
        $updated = $this->envEditor->upsert([
            'ENCRYPTION_FORM_PUBLIC_KEY'  => $publicKey,
            'ENCRYPTION_FORM_PRIVATE_KEY' => $privateKey,
        ]);

        if (!$updated) {
            $this->error('Failed to write keys to .env file.');
            return false;
        }

        $this->call('config:clear');

        return true;
    }
}