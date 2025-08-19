<?php

namespace Bespredel\EncryptionForm;

use Bespredel\EncryptionForm\Blade\Directives;
use Bespredel\EncryptionForm\Console\Commands\GenerateEncryptionKeys;
use Bespredel\EncryptionForm\Middleware\DecryptRequestFields;
use Bespredel\EncryptionForm\Services\Contracts\DecryptorInterface;
use Bespredel\EncryptionForm\Services\Decryptor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
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

        // Registering helpers
        if (file_exists(__DIR__ . '/Support/helpers.php')) {
            require_once __DIR__ . '/Support/helpers.php';
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