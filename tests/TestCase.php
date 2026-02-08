<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests;

use Bespredel\EncryptionForm\EncryptionFormServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            EncryptionFormServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('encryption-form.enabled', true);
        config()->set('encryption-form.prefix', 'ENCF:');
    }
}
