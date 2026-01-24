<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm;

use Bespredel\EncryptionForm\Blade\Directives;
use Bespredel\EncryptionForm\Console\Commands\GenerateEncryptionKeys;
use Bespredel\EncryptionForm\Middleware\DecryptRequestFields;
use Bespredel\EncryptionForm\Services\Contracts\DecryptorInterface;
use Bespredel\EncryptionForm\Services\Decryptor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class EncryptionFormServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Router $router
     *
     * @return void
     *
     * @throws \Exception
     */
    public function boot(Router $router): void
    {
        $langPath = $this->getLangPath('vendor/encryption-form');

        $this->publishes([
            __DIR__ . '/../config/encryption-form.php' => config_path('encryption-form.php'),
            __DIR__ . '/../resources/js'               => public_path('vendor/encryption-form/js'),
            __DIR__ . '/../resources/css'              => public_path('vendor/encryption-form/css'),
            __DIR__ . '/../resources/lang'             => $langPath,
        ], 'encryption-form');

        // Registering the path to language files
        if (!is_dir($langPath)) {
            $langPath = __DIR__ . '/../resources/lang';
        }
        $this->loadTranslationsFrom($langPath, 'encryption-form');

        $this->registerMiddleware($router);
        $this->registerCommands();

        Directives::register();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(DecryptorInterface::class, Decryptor::class);

        $this->mergeConfigFrom(__DIR__ . '/../config/encryption-form.php', 'encryption-form');

        // Validate configuration if encryption is enabled
        $this->validateConfiguration();

        // Registering helpers
        if (file_exists(__DIR__ . '/Support/helpers.php')) {
            require_once __DIR__ . '/Support/helpers.php';
        }
    }

    /**
     * Validate encryption form configuration
     *
     * @return void
     */
    protected function validateConfiguration(): void
    {
        if (!config('encryption-form.enabled', true)) {
            return;
        }

        $publicKey = config('encryption-form.public_key');
        $privateKey = config('encryption-form.private_key');

        // Only validate if keys are set
        if ($publicKey !== null || $privateKey !== null) {
            if (empty($publicKey)) {
                Log::warning('Encryption form is enabled but public key is not set');
            }

            if (empty($privateKey)) {
                Log::warning('Encryption form is enabled but private key is not set');
            }

            // Validate key format if both are set
            if (!empty($publicKey) && !empty($privateKey)) {
                $publicKeyResource = openssl_pkey_get_public($publicKey);
                $privateKeyResource = openssl_pkey_get_private($privateKey);

                if ($publicKeyResource === false) {
                    Log::warning('Encryption form public key is invalid');
                }

                if ($privateKeyResource === false) {
                    Log::warning('Encryption form private key is invalid');
                }
            }
        }
    }

    /**
     * Register middleware.
     *
     * @param Router $router
     *
     * @return void
     */
    public function registerMiddleware(Router $router): void
    {
        $router->aliasMiddleware('decrypt-form', DecryptRequestFields::class);
    }

    /**
     * Register the commands.
     *
     * @return void
     */
    public function registerCommands(): void
    {
        $this->commands([
            GenerateEncryptionKeys::class,
        ]);

        if (config('encryption-form.enabled', true) && config('encryption-form.key_rotation.enabled', false)) {
            $this->app->booted(function () {
                app(Schedule::class)
                    ->command('encryption-form:generate-keys')
                    ->cron(config('encryption-form.key_rotation.cron_expression', '0 0 * * *'));
            });
        }
    }

    /**
     * Get the language path.
     *
     * @param string $relativePath
     *
     * @return string
     */
    protected function getLangPath(string $relativePath): string
    {
        return function_exists('lang_path')
            ? lang_path($relativePath)
            : resource_path('lang/' . $relativePath);
    }
}