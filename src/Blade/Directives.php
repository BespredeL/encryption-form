<?php

namespace Bespredel\EncryptionForm\Blade;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;

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