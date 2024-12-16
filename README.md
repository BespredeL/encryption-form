# Encryption Form

[![EN](https://img.shields.io/badge/README-EN-blue.svg)](https://github.com/bespredel/encryption-form/blob/master/README.md)
[![RU](https://img.shields.io/badge/README-RU-blue.svg)](https://github.com/bespredel/encryption-form/blob/master/README_RU.md)

A Laravel package to securely encrypt form fields on the client-side using public key encryption and decrypt them on the server-side using the private
key. This package integrates seamlessly with Laravel Blade templates and requires minimal configuration.

---

## Features

- **RSA Encryption**: Uses `JSEncrypt` for secure RSA encryption.
- **HTML Attribute Control**: Specify which fields to encrypt using `data-encrypt="true"`.
- **Flexible Form Encryption**: Target specific forms using `data-encrypt-form` attribute.
- **Blade Directive**: Automatically inject encryption scripts with `@encryptFormScripts`.
- **Simple Key Management**: Easily configure keys via `.env` or generate new keys via artisan commands.
- **Zero Dependencies**: No NPM required; all scripts are included in the package.

---

## Installation

1. **Install the Package**:
   ```bash
   composer require bespredel/encryption-form
   ```
2. **Publish Config and Scripts**:
   ```bash
   php artisan vendor:publish --tag=encryption-form
   ```
3. **Add RSA Keys to ```.env```**:
   ```bash
   ENCRYPTION_FORM_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----"
   ENCRYPTION_FORM_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----...-----END PRIVATE KEY-----"
   ```

   If you don't have keys, you can generate them using the following commands:
   ```bash
   php artisan encryption-form:generate-keys
   ```

4. **Include the Blade Directive in Your Template**:
   Add `@encryptFormScripts` to your layout file or specific views where forms are encrypted.

## Usage

### Middleware

For auto decryption of form data, add the `DecryptRequestFields` middleware to your `Kernel`:

```php
Add the middleware to your Kernel

protected $middleware = [
    // Other middleware
    \Bespredel\EncryptionForm\Middleware\DecryptRequestFields::class
]
```

or use it in a route:

```php
Route::middleware('decrypt-form')->group(function () {
    // Your code
})
```

### HTML Form Example

In your Blade template:

```html

<head>
    @encryptFormScripts
</head>

<form data-encrypt-form action="/submit" method="POST">
    @csrf
    <input type="text" name="name" data-encrypt="true" placeholder="Enter your name" />
    <input type="email" name="email" data-encrypt="true" placeholder="Enter your email" />
    <input type="text" name="address" placeholder="Enter your address" />
    <button type="submit">Submit</button>
</form>
```

- Add `data-encrypt-form` to the <form> tag to enable encryption for that form.
- Use `data-encrypt="true"` on individual fields that require encryption.

Field types currently unavailable for encryption:

- checkbox
- radio
- select
- file

### Manual decrypting data on the server

Use the `RequestDecryptor` class to decrypt data on the server:

```php
use Bespredel\EncryptionForm\Services\RequestDecryptor;

$value = $request->input('name'); // Example for 'name' field
$privateKey = Config::get('encryption_form.private_key');

$decryptedValue = RequestDecryptor::decryptValue($value, $privateKey);
```

Or use the `openssl_private_decrypt` function to decrypt data on the server:

```php
use Illuminate\Support\Facades\Config;

$privateKey = Config::get('encryption_form.private_key');

$encryptedData = $request->input('name'); // Example for 'name' field
$decryptedData = null;

$decodedValue = base64_decode((string)str($encryptedData)->after('ENCF:'), true);
openssl_private_decrypt($decodedValue, $decryptedData, $privateKey);

echo $decryptedData; // Output the decrypted value
```

## Commands

### Generate New RSA Keys

To generate a new pair of RSA keys:

```bash
php artisan encryption-form:generate-keys
```

This will update the keys in your `.env` file.

## Configuration

### Config File:

```config/encryption_form.php```

```php
return [
   'public_key' => env('ENCRYPTION_FORM_PUBLIC_KEY'),
   'private_key' => env('ENCRYPTION_FORM_PRIVATE_KEY'),
   'key_rotation' => [
      'enabled'         => env('ENCRYPTION_FORM_KEY_ROTATION_ENABLED', false),
      'cron_expression' => '0 0 * * *',
   ],
];
```

## Key Rotation via Scheduler

You can schedule automatic key rotation via the `key_rotation` key in the config file.:

```php
return [
    ...
   'key_rotation' => [
     'enabled'         => env('ENCRYPTION_FORM_KEY_ROTATION_ENABLED', false),
     'cron_expression' => '0 0 * * *',
   ],
];
```

## Contributing

1. Fork the repository.
2. Create your feature branch: `git checkout -b feature/my-feature`.
3. Commit your changes: `git commit -m 'Add some feature'`.
4. Push to the branch: `git push origin feature/my-feature`.
5. Open a pull request.

## Security

PLEASE DON'T DISCLOSE SECURITY-RELATED ISSUES PUBLICLY.

If you discover any security related issues, please email [hello@bespredel.name](hello@bespredel.name) instead of using the issue tracker.

## Acknowledgements

I would like to thank the authors and contributors of the [JSEncrypt](https://github.com/travist/jsencrypt) library for providing a secure RSA
encryption solution for client-side data encryption.

## License

This package is open-source software licensed under the MIT license.
