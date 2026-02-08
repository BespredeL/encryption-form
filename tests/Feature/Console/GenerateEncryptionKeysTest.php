<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests\Feature\Console;

use Bespredel\EncryptionForm\Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateEncryptionKeysTest extends TestCase
{
    public function testCommandWritesKeysToEnvFile(): void
    {
        $basePath = $this->app->basePath();
        $envPath = $basePath . DIRECTORY_SEPARATOR . '.env';

        if (getenv('CI') !== false && getenv('CI') !== '') {
            $this->markTestSkipped('GenerateEncryptionKeys command test skipped in CI (writes to .env).');
        }

        $backup = null;
        if (File::exists($envPath)) {
            $backup = File::get($envPath);
        } else {
            File::put($envPath, "APP_KEY=test\n");
        }

        try {
            $this->artisan('encryption-form:generate-keys')
                ->expectsOutputToContain('saved to .env')
                ->assertSuccessful();

            $content = File::get($envPath);
            $this->assertStringContainsString('ENCRYPTION_FORM_PUBLIC_KEY=', $content);
            $this->assertStringContainsString('ENCRYPTION_FORM_PRIVATE_KEY=', $content);
            $this->assertStringContainsString('-----BEGIN PUBLIC KEY-----', $content);
            $this->assertStringContainsString('-----BEGIN PRIVATE KEY-----', $content);
        } finally {
            if ($backup !== null) {
                File::put($envPath, $backup);
            } elseif (File::exists($envPath)) {
                File::delete($envPath);
            }
        }
    }
}
