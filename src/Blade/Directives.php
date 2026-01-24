<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Blade;

use Illuminate\Support\Facades\Blade;

class Directives
{
    /**
     * Registering Blade Directives
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function register(): void
    {
        // Styles injection
        Blade::directive('encryptFormStyles', function () {
            return "<?php echo \\Bespredel\\EncryptionForm\\Support\\Render::styles(); ?>";
        });

        // Scripts injection
        Blade::directive('encryptFormScripts', function () {
            return "<?php echo \\Bespredel\\EncryptionForm\\Support\\Render::scripts(); ?>";
        });
    }
}