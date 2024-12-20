<?php

namespace Bespredel\EncryptionForm;

use Bespredel\EncryptionForm\Blade\Directives;
use Bespredel\EncryptionForm\Console\Commands\GenerateEncryptionKeys;
use Bespredel\EncryptionForm\Middleware\DecryptRequestFields;
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
            __DIR__ . '/../resources/lang'             => $langPath,
        ], 'encryption-form');

        // Registering the path to language files
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'encryption-form');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'encryption-form');
        }

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
        $this->mergeConfigFrom(
            __DIR__ . '/../config/encryption-form.php',
            'encryption-form'
        );
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

        if (config('encryption-form.enabled') && config('encryption-form.key_rotation.enabled')) {
            $this->app->booted(function () {
                app(Schedule::class)
                    ->command('encryption-form:generate-keys')
                    ->cron(config('encryption-form.key_rotation.cron_expression'));
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