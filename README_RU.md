# Encryption Form

[![Readme EN](https://img.shields.io/badge/README-EN-blue.svg)](https://github.com/bespredel/encryption-form/blob/master/README.md)
[![Readme RU](https://img.shields.io/badge/README-RU-blue.svg)](https://github.com/bespredel/encryption-form/blob/master/README_RU.md)
[![GitHub license](https://img.shields.io/badge/license-MIT-458a7b.svg)](https://github.com/bespredel/encryption-form/blob/master/LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/bespredel/encryption-form.svg)](https://packagist.org/packages/bespredel/encryption-form)

[![Latest Version](https://img.shields.io/github/v/release/bespredel/encryption-form?logo=github)](https://github.com/bespredel/encryption-form/releases)
[![Latest Version Packagist](https://img.shields.io/packagist/v/bespredel/encryption-form.svg?logo=packagist&logoColor=white&color=F28D1A)](https://packagist.org/packages/bespredel/encryption-form)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/bespredel/encryption-form.svg?logo=php&logoColor=white&color=777BB4)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D9-FF2D20?logo=laravel)](https://laravel.com)

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
   
   <div class="encrypt-form-status"></div> <!-- Необязательный элемент для отображения статуса работы шифрования -->
   
    <button type="submit">Submit</button>
</form>
```

- Добавьте `data-encrypt-form` в тег `<form>`, чтобы включить шифрование для этой формы.
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
$privateKey = config('encryption-form.private_key');

$decryptedValue = RequestDecryptor::decryptValue($value, $privateKey);
```

Или используйте функцию `openssl_private_decrypt` для расшифровки данных на сервере:

```php
$privateKey = config('encryption-form.private_key');

$encryptedData = $request->input('name'); // Пример поля 'name' имя
$decryptedData = null;

$decodedValue = base64_decode((string)str($encryptedData)->after('ENCF:'), true);
openssl_private_decrypt($decodedValue, $decryptedData, $privateKey);

echo $decryptedData; // Вывод расшифрованного значения
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

```config/encryption-form.php```

```php
return [
   'public_key'   => env('ENCRYPTION_FORM_PUBLIC_KEY'),
   'private_key'  => env('ENCRYPTION_FORM_PRIVATE_KEY'),
   'prefix'       => env('ENCRYPTION_FORM_PREFIX', 'ENCF:'),
   'key_rotation' => [
      'enabled'         => env('ENCRYPTION_FORM_KEY_ROTATION_ENABLED', false),
      'cron_expression' => '0 0 * * *',
   ],
];
```

## Обновление ключей с помощью планировщика

Вы можете запланировать автоматическую смену ключей шифрования с помощью `key_rotation` в конфигурационном файле.:

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