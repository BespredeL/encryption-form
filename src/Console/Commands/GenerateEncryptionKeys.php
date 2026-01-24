<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Console\Commands;

use Illuminate\Console\Command;
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
     * @return int
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

        openssl_pkey_export($keyPair, $privateKey);

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
     * @param string $privateKey
     * @param string $publicKey
     *
     * @return bool
     */
    protected function saveKeysToEnv(string $privateKey, string $publicKey): bool
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $this->error('The .env file does not exist or cannot be accessed.');
            return false;
        }

        $envContent = File::get($envPath);

        // Remove existing keys (handle both with and without quotes, and multiline keys)
        $envContent = preg_replace('/\nENCRYPTION_FORM_PUBLIC_KEY=.*?(?=\n|$)/m', '', $envContent);
        $envContent = preg_replace('/\nENCRYPTION_FORM_PRIVATE_KEY=.*?(?=\n|$)/m', '', $envContent);

        // Escape keys for .env file (handle multiline keys properly)
        $escapedPublicKey = str_replace(["\n", "\r"], ['\\n', '\\r'], $publicKey);
        $escapedPrivateKey = str_replace(["\n", "\r"], ['\\n', '\\r'], $privateKey);

        // Add new keys at the end
        $envContent .= "\nENCRYPTION_FORM_PUBLIC_KEY=\"{$escapedPublicKey}\"";
        $envContent .= "\nENCRYPTION_FORM_PRIVATE_KEY=\"{$escapedPrivateKey}\"";

        if (!File::put($envPath, $envContent)) {
            $this->error('Failed to write keys to .env file.');
            return false;
        }

        $this->call('config:clear'); // Resetting the configuration cache

        return true;
    }
}