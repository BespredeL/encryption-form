<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Tests\Feature\Middleware;

use Bespredel\EncryptionForm\Exceptions\DecryptionException;
use Bespredel\EncryptionForm\Services\Decryptor;
use Bespredel\EncryptionForm\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class DecryptRequestFieldsTest extends TestCase
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
        $this->privateKey = $privateKey;
        $this->publicKey = openssl_pkey_get_details($res)['key'];
        Config::set('encryption-form.private_key', $this->privateKey);
        Config::set('encryption-form.public_key', $this->publicKey);
        Config::set('encryption-form.prefix', $this->prefix);
        Config::set('encryption-form.enabled', true);
        Config::set('encryption-form.skip_for_ips', []);
        Config::set('encryption-form.strict_mode', false);
    }

    public function testMiddlewareDecryptsEncryptedFields(): void
    {
        $decryptor = new Decryptor();
        $plain = 'secret data';
        openssl_public_encrypt($plain, $encrypted, $this->publicKey);
        $encryptedValue = $this->prefix . base64_encode($encrypted);

        $request = Request::create('/test', 'POST', [
            'name' => $encryptedValue,
        ]);
        $request->headers->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(\Bespredel\EncryptionForm\Middleware\DecryptRequestFields::class);
        $next = function ($req) {
            return $req;
        };
        $middleware->handle($request, $next);

        $this->assertSame('secret data', $request->input('name'));
    }

    public function testMiddlewarePreservesSystemFields(): void
    {
        $request = Request::create('/test', 'POST', [
            '_token' => 'csrf-token-123',
            '_method' => 'POST',
            'plain_field' => 'not_encrypted',
        ]);
        $request->headers->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(\Bespredel\EncryptionForm\Middleware\DecryptRequestFields::class);
        $next = function ($req) {
            return $req;
        };
        $middleware->handle($request, $next);

        $this->assertSame('csrf-token-123', $request->input('_token'));
        $this->assertSame('POST', $request->input('_method'));
        $this->assertSame('not_encrypted', $request->input('plain_field'));
    }

    public function testMiddlewareSkipsDecryptionWhenDisabled(): void
    {
        Config::set('encryption-form.enabled', false);

        $decryptor = new Decryptor();
        $plain = 'secret';
        openssl_public_encrypt($plain, $encrypted, $this->publicKey);
        $encryptedValue = $this->prefix . base64_encode($encrypted);

        $request = Request::create('/test', 'POST', ['name' => $encryptedValue]);
        $request->headers->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(\Bespredel\EncryptionForm\Middleware\DecryptRequestFields::class);
        $next = function ($req) {
            return $req;
        };
        $middleware->handle($request, $next);

        $this->assertSame($encryptedValue, $request->input('name'));
    }

    public function testMiddlewareSkipsDecryptionWhenPrivateKeyNotSet(): void
    {
        Config::set('encryption-form.private_key', null);

        $request = Request::create('/test', 'POST', ['field' => 'value']);
        $request->headers->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(\Bespredel\EncryptionForm\Middleware\DecryptRequestFields::class);
        $next = function ($req) {
            return $req;
        };
        $middleware->handle($request, $next);

        $this->assertSame('value', $request->input('field'));
    }

    public function testMiddlewareThrowsWhenStrictModeEnabledAndFieldCannotBeDecrypted(): void
    {
        Config::set('encryption-form.strict_mode', true);

        $request = Request::create('/test', 'POST', ['name' => $this->prefix . 'invalid_base64_payload']);
        $request->headers->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(\Bespredel\EncryptionForm\Middleware\DecryptRequestFields::class);
        $next = function ($req) {
            return $req;
        };

        $this->expectException(DecryptionException::class);
        $middleware->handle($request, $next);
    }
}
