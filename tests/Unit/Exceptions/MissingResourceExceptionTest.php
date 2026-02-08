<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests\Unit\Exceptions;

use Bespredel\EncryptionForm\Exceptions\EncryptionFormException;
use Bespredel\EncryptionForm\Exceptions\MissingResourceException;
use PHPUnit\Framework\TestCase;

class MissingResourceExceptionTest extends TestCase
{
    public function testExtendsEncryptionFormException(): void
    {
        $e = new MissingResourceException('test');
        $this->assertInstanceOf(EncryptionFormException::class, $e);
    }

    public function testMessageIsPreserved(): void
    {
        $msg = 'Required CSS files are missing.';
        $e = new MissingResourceException($msg);
        $this->assertSame($msg, $e->getMessage());
    }
}
