# Encryption Form

[![EN](https://img.shields.io/badge/README-EN-blue.svg)](https://github.com/bespredel/encryption-form/blob/master/README.md)
[![RU](https://img.shields.io/badge/README-RU-blue.svg)](https://github.com/bespredel/encryption-form/blob/master/README_RU.md)

Пакет Laravel для надежного шифрования полей формы на стороне клиента с помощью шифрования с открытым ключом и их расшифровки на стороне сервера с
помощью закрытого
ключа. Этот пакет легко интегрируется с шаблонами Laravel Blade и требует минимальной настройки.

---

## Особенности

- **RSA-шифрование**: Использует `JSEncrypt` для безопасного RSA-шифрования.
- **Управление атрибутами HTML**: Укажите, какие поля шифровать, используя `data-encrypt="true"`.
- **Гибкое шифрование форм**: Ориентируйтесь на конкретные формы, используя атрибут `data-encrypt-form`.
- **Директива Blade**: Автоматическое внедрение сценариев шифрования с помощью "`@encryptFormScripts`".
- **Простое управление ключами**: Простая настройка ключей с помощью "`.env`" или генерация новых ключей с помощью команд artisan.
- **Нулевые зависимости**: NPM не требуется; все скрипты включены в пакет.

---

## Установка

1. **Установите пакет**:
   ```bash
   composer require bespredel/encryption-form
   ```
2. **Опубликуйте конфигурацию и скрипты**:
   ```bash
   php artisan vendor:publish --tag=encryption-form
   ```
3. **Добавьте ключи RSA в ``.env``**:
   ```bash
   ENCRYPTION_FORM_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----"
   ENCRYPTION_FORM_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----...-----END PRIVATE KEY-----"
   ```

   Если у вас нет ключей, вы можете сгенерировать их с помощью следующей команды:
   ```bash
   php artisan encryption-form:generate-keys
   ```

4. **Включите директиву Blade в свой шаблон**:
   Добавьте `@encryptFormScripts` в свой файл макета или в конкретные представления, в которых формы должны быть зашифрованы.

## Использование

### Middleware

Для автоматической расшифровки данных формы добавьте middleware `DecryptRequestFields` в `Kernel`:

```php
Добавьте middleware в Kernel

protected $middleware = [
    // Другое промежуточное программное обеспечение
    \Bespredel\EncryptionForm\Middleware\DecryptRequestFields::класс,
]
```

или используйте его в маршруте:

```php
Route::middleware('decrypt-form')->group(function () {
    // Your code
})
```

### Пример HTML-формы

В вашем шаблоне Blade:

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

- Добавьте `data-encrypt-form` в тег <form>, чтобы включить шифрование для этой формы.
- Используйте `data-encrypt="true"` для полей, которые требуют шифрования.

Типы полей, которые в данный момент недоступны для шифрования:

- checkbox
- radio
- select
- file

### Расшифровка данных вручную на сервере

Используйте класс `RequestDecryptor` для расшифровки данных на сервере:

```php
use Bespredel\EncryptionForm\Services\RequestDecryptor;

$value = $request->input('name'); // Example for 'name' field
$privateKey = Config::get('encryption_form.private_key');

$decryptedValue = RequestDecryptor::decryptValue($value, $privateKey);
```

Или используйте функцию `openssl_private_decrypt` для расшифровки данных на сервере:

```php
use Illuminate\Support\Facades\Config;

$privateKey = Config::get('encryption_form.private_key');

$encryptedData = $request->input('name'); // Example for 'name' field
$decryptedData = null;

$decodedValue = base64_decode((string)str($encryptedData)->after('ENCF:'), true);
openssl_private_decrypt($decodedValue, $decryptedData, $privateKey);

echo $decryptedData; // Output the decrypted value
```

## Команды

### Генерировать новые ключи RSA

Чтобы сгенерировать новую пару ключей RSA:

```bash
php artisan encryption-form:generate-keys
```

Это обновит ключи в вашем файле .env.

## Конфигурация

### Конфигурационный файл:

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

## Обновление ключей с помощью планировщика

Вы можете запланировать автоматическую смену клавиш с помощью клавиши `key_rotation` в конфигурационном файле.:

```php
return [
    ...
   'key_rotation' => [
     'enabled'         => env('ENCRYPTION_FORM_KEY_ROTATION_ENABLED', false),
     'cron_expression' => '0 0 * * *',
   ],
];
```

## Способствовать развитию

1. Форкните репозиторий.
2. Создайте свою ветку: `git checkout -b feature/my-feature`.
3. Закомитьте свои изменения: `git commit -m "Добавлена функция"`.
4. Запушьте ветку: `git push origin feature/my-feature`.
5. Отправьте пулл-реквест.

## Безопасность

ПОЖАЛУЙСТА, НЕ СООБЩАЙТЕ О ПРОБЛЕМАХ, СВЯЗАННЫХ С БЕЗОПАСНОСТЬЮ, ПУБЛИЧНО.

Если вы обнаружите какие-либо проблемы, связанные с безопасностью, пожалуйста, напишите мне по электронной
почте [hello@bespredel.name](hello@bespredel.name) вместо того,
чтобы использовать систему отслеживания проблем.

## Благодарности

Я хотел бы поблагодарить авторов и соавторов библиотеки [JSEncrypt](https://github.com/travist/jsencrypt) за предоставление надежного RSA решения для
шифрования данных на стороне клиента.

## Лицензия

Этот пакет представляет собой программное обеспечение с открытым исходным кодом, лицензированное по лицензии MIT.