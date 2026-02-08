<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests\Unit;

use Bespredel\EncryptionForm\Services\Decryptor;
use Bespredel\EncryptionForm\Tests\TestCase;

class DecryptorTest extends TestCase
{
    private string $privateKey;

    private string $publicKey;

    private string $prefix = 'ENCF:';

    protected function setUp(): void
    {
        parent::setUp();
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    public function testDecryptValueReturnsNullOnEmptyValue(): void
    {
        $decryptor = new Decryptor();
        $this->assertNull($decryptor->decryptValue('', $this->privateKey, $this->prefix));
    }

    public function testDecryptValueReturnsNullOnEmptyPrivateKey(): void
    {
        $decryptor = new Decryptor();
        $data = 'secret';
        openssl_public_encrypt($data, $encrypted, $this->publicKey);
        $encoded = base64_encode($encrypted);
        $this->assertNull($decryptor->decryptValue($this->prefix . $encoded, '', $this->prefix));
    }

    public function testDecryptValueReturnsNullOnInvalidPrefix(): void
    {
        $decryptor = new Decryptor();
        $encrypted = base64_encode('test');
        $result = $decryptor->decryptValue('WRONG:' . $encrypted, $this->privateKey, $this->prefix);
        $this->assertNull($result);
    }

    public function testDecryptValueReturnsNullOnInvalidBase64(): void
    {
        $decryptor = new Decryptor();
        $result = $decryptor->decryptValue($this->prefix . 'not_base64', $this->privateKey, $this->prefix);
        $this->assertNull($result);
    }

    public function testDecryptValueWithWrongKeyDoesNotReturnOriginalPlaintext(): void
    {
        $decryptor = new Decryptor();
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($res, $otherPrivateKey);
        $data = 'secret';
        openssl_public_encrypt($data, $encrypted, $this->publicKey);
        $encoded = base64_encode($encrypted);
        $result = $decryptor->decryptValue($this->prefix . $encoded, $otherPrivateKey, $this->prefix);
        // With wrong key, OpenSSL may return garbage or null; must not return original plaintext
        $this->assertNotSame($data, $result);
    }

    public function testDecryptValueSuccess(): void
    {
        $decryptor = new Decryptor();
        $data = 'secret';
        openssl_public_encrypt($data, $encrypted, $this->publicKey);
        $encoded = base64_encode($encrypted);
        $result = $decryptor->decryptValue($this->prefix . $encoded, $this->privateKey, $this->prefix);
        $this->assertSame($data, $result);
    }

    public function testDecryptValuesArray(): void
    {
        $decryptor = new Decryptor();
        $data = 'secret';
        openssl_public_encrypt($data, $encrypted, $this->publicKey);
        $encoded = base64_encode($encrypted);
        $fields = [
            'field1' => $this->prefix . $encoded,
            'field2' => 'not_encrypted',
        ];
        $result = $decryptor->decryptValues($fields, $this->privateKey, $this->prefix);
        $this->assertSame($data, $result['field1']);
        $this->assertSame('not_encrypted', $result['field2']);
    }

    public function testDecryptValuesLeavesNonEncryptedAndNonStringValues(): void
    {
        $decryptor = new Decryptor();
        $fields = [
            'number' => 42,
            'nested' => ['a' => 'b'],
        ];
        $result = $decryptor->decryptValues($fields, $this->privateKey, $this->prefix);
        $this->assertSame(42, $result['number']);
        $this->assertSame(['a' => 'b'], $result['nested']);
    }
}
