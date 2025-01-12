<?php

namespace Bespredel\EncryptionForm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

class GenerateEncryptionKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encryption-form:generate-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new RSA key pair to encrypt the forms.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($keyPair, $privateKey);

        $publicKey = openssl_pkey_get_details($keyPair)['key'];

        $this->saveKeysToEnv($privateKey, $publicKey);

        $this->info('New RSA key pair generated and saved to .env');
    }

    /**
     * Saving keys to a .env file
     *
     * @param $privateKey
     * @param $publicKey
     *
     * @return void
     */
    protected function saveKeysToEnv($privateKey, $publicKey): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $this->error('The .env file does not exist or cannot be accessed.');
            return;
        }

        $envContent = File::get($envPath);

        // Remove existing keys
        $envContent = preg_replace('/\nENCRYPTION_FORM_PUBLIC_KEY="[^"]*"/m', '', $envContent);
        $envContent = preg_replace('/\nENCRYPTION_FORM_PRIVATE_KEY="[^"]*"/m', '', $envContent);

        // Add new public keys
        if (!preg_match('/^ENCRYPTION_FORM_PUBLIC_KEY="/m', $envContent)) {
            $envContent .= "\nENCRYPTION_FORM_PUBLIC_KEY=\"{$publicKey}\"";
        }

        // Add new private keys
        if (!preg_match('/^ENCRYPTION_FORM_PRIVATE_KEY="/m', $envContent)) {
            $envContent .= "\nENCRYPTION_FORM_PRIVATE_KEY=\"{$privateKey}\"";
        }

        File::put($envPath, $envContent);

        $this->call('config:clear'); // Resetting the configuration cache
    }
}