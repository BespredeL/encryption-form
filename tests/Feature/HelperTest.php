<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests\Feature;

use Bespredel\EncryptionForm\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class HelperTest extends TestCase
{
    public function testEncryptionFormEnabledReturnsFalseWhenDisabled(): void
    {
        Config::set('encryption-form.enabled', false);
        $this->assertFalse(encryption_form_enabled());
    }

    public function testEncryptionFormEnabledReturnsTrueWhenEnabled(): void
    {
        Config::set('encryption-form.enabled', true);
        Config::set('encryption-form.skip_for_ips', []);
        $this->assertTrue(encryption_form_enabled());
    }

    public function testEncryptionFormEnabledReturnsFalseForSkippedIp(): void
    {
        Config::set('encryption-form.enabled', true);
        Config::set('encryption-form.skip_for_ips', ['127.0.0.1']);

        $this->app['request']->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertFalse(encryption_form_enabled());
    }

    public function testEncryptionFormEnabledReturnsFalseForSkippedWildcardIp(): void
    {
        Config::set('encryption-form.enabled', true);
        Config::set('encryption-form.skip_for_ips', ['192.168.*']);

        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.100');

        $this->assertFalse(encryption_form_enabled());
    }
}
