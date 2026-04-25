<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests\Feature;

use Bespredel\EncryptionForm\Exceptions\MissingResourceException;
use Bespredel\EncryptionForm\Support\Render;
use Bespredel\EncryptionForm\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class RenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('encryption-form.enabled', true);
        Config::set('encryption-form.skip_for_ips', []);
        Config::set('encryption-form.prefix', 'ENCF:');
        Config::set('encryption-form.public_key', "-----BEGIN PUBLIC KEY-----\nTEST\n-----END PUBLIC KEY-----");

        Cache::flush();
        $this->prepareAssets();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('vendor/encryption-form'));
        parent::tearDown();
    }

    public function testStylesRendersIntegrityTag(): void
    {
        $styles = Render::styles();

        $this->assertStringContainsString('rel="stylesheet"', $styles);
        $this->assertStringContainsString('/vendor/encryption-form/css/form-encrypt.min.css', $styles);
        $this->assertStringContainsString('integrity="sha384-', $styles);
    }

    public function testScriptsRendersAssetsAndPayload(): void
    {
        $scripts = Render::scripts();

        $this->assertStringContainsString('/vendor/encryption-form/js/jsencrypt.min.js', $scripts);
        $this->assertStringContainsString('/vendor/encryption-form/js/form-encrypt.min.js', $scripts);
        $this->assertStringContainsString('window.ENCRYPTION_FORM', $scripts);
        $this->assertStringContainsString("'prefix': `ENCF:`", $scripts);
    }

    public function testStylesThrowsExceptionWhenAssetIsMissing(): void
    {
        File::delete(public_path('vendor/encryption-form/css/form-encrypt.min.css'));

        $this->expectException(MissingResourceException::class);
        Render::styles();
    }

    public function testScriptsThrowsExceptionWhenAssetIsMissing(): void
    {
        File::delete(public_path('vendor/encryption-form/js/form-encrypt.min.js'));

        $this->expectException(MissingResourceException::class);
        Render::scripts();
    }

    public function testStylesAndScriptsReturnEmptyWhenDisabled(): void
    {
        Config::set('encryption-form.enabled', false);

        $this->assertSame('', Render::styles());
        $this->assertSame('', Render::scripts());
    }

    private function prepareAssets(): void
    {
        File::ensureDirectoryExists(public_path('vendor/encryption-form/css'));
        File::ensureDirectoryExists(public_path('vendor/encryption-form/js'));

        File::put(public_path('vendor/encryption-form/css/form-encrypt.min.css'), 'body{color:#000;}');
        File::put(public_path('vendor/encryption-form/js/jsencrypt.min.js'), 'window.JSEncrypt=function(){};');
        File::put(public_path('vendor/encryption-form/js/form-encrypt.min.js'), 'window.FormEncryptor={};');
    }
}
