<?php

namespace Bespredel\EncryptionForm;

use Bespredel\EncryptionForm\Blade\Directives;
use Bespredel\EncryptionForm\Console\Commands\GenerateEncryptionKeys;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Bespredel\EncryptionForm\Middleware\DecryptRequestFields;

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
        $langPath = 'vendor/encryption-form';
        $langPath = (function_exists('lang_path'))
            ? lang_path($langPath)
            : resource_path('lang/' . $langPath);

        $this->publishes([
            __DIR__ . '/../config/encryption_form.php' => config_path('encryption_form.php'),
            __DIR__ . '/../resources/js'               => public_path('vendor/encryption-form/js'),
            //__DIR__ . '/../resources/lang'             => $langPath,
        ], 'decrypt-form');

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
            __DIR__ . '/../config/encryption_form.php',
            'encryption_form'
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

        if (config('encryption_form.key_rotation.enabled')) {
            $this->app->booted(function () {
                app(Schedule::class)
                    ->command('encryption-form:generate-keys')
                    ->cron(config('encryption_form.key_rotation.cron_expression'));
            });
        }
    }
}