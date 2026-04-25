<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests\Feature;

use Bespredel\EncryptionForm\Console\Commands\GenerateEncryptionKeys;
use Bespredel\EncryptionForm\Middleware\DecryptRequestFields;
use Bespredel\EncryptionForm\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testRegistersDecryptFormMiddlewareAlias(): void
    {
        $middleware = $this->app['router']->getMiddleware();

        $this->assertArrayHasKey('decrypt-form', $middleware);
        $this->assertSame(DecryptRequestFields::class, $middleware['decrypt-form']);
    }

    public function testRegistersGenerateKeysCommand(): void
    {
        $commands = $this->app->make('Illuminate\\Contracts\\Console\\Kernel')->all();

        $this->assertArrayHasKey('encryption-form:generate-keys', $commands);
        $this->assertInstanceOf(GenerateEncryptionKeys::class, $commands['encryption-form:generate-keys']);
    }
}
