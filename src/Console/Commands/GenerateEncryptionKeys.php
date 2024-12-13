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
    protected $description = 'Generate a new RSA key pair for encryption';

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
     *
     * @throws FileNotFoundException
     */
    protected function saveKeysToEnv($privateKey, $publicKey): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $this->error('The .env file does not exist.');
            return;
        }

        $envContent = File::get($envPath);

        // Replace existing keys or add new ones
        $envContent = preg_replace('/ENCRYPTION_FORM_PUBLIC_KEY=".*?"/', '', $envContent);
        $envContent = preg_replace('/ENCRYPTION_FORM_PRIVATE_KEY=".*?"/', '', $envContent);
        $envContent .= "\nENCRYPTION_FORM_PUBLIC_KEY=\"{$publicKey}\"\n";
        $envContent .= "ENCRYPTION_FORM_PRIVATE_KEY=\"{$privateKey}\"\n";

        File::put($envPath, $envContent);

        $this->call('config:clear'); // Clearing the configuration cache
    }
}
