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
}
