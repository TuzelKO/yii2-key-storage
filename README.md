# Yii2 Key Storage extension

[![Project Status: Active](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)
[![Tests](https://github.com/TuzelKO/yii2-key-storage/actions/workflows/tests.yml/badge.svg)](https://github.com/TuzelKO/yii2-key-storage/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/tuzelko/yii2-key-storage)](https://packagist.org/packages/tuzelko/yii2-key-storage)
[![PHP Version](https://img.shields.io/packagist/dependency-v/tuzelko/yii2-key-storage/php)](https://packagist.org/packages/tuzelko/yii2-key-storage)
[![Total Downloads](https://img.shields.io/packagist/dt/tuzelko/yii2-key-storage)](https://packagist.org/packages/tuzelko/yii2-key-storage)
[![License](https://img.shields.io/github/license/TuzelKO/yii2-key-storage)](https://github.com/TuzelKO/yii2-key-storage/blob/main/LICENSE)

Named cryptographic key storage for the [Yii2](https://www.yiiframework.com/) framework.

Solves one recurring problem: *"give me the raw bytes of key X, taken from env, decoded and validated — and fail loudly at the first use if the key is missing or malformed"*. Encryption keys, signing keys, HMAC secrets, TOTP seeds — all behind one named registry instead of ad-hoc `base64_decode(getenv(...))` calls scattered around the codebase.

## Features

- **Named keys** — one registry, keys referenced by name
- **Text sources** — `base64` (standard and url-safe alphabets) or `hex`, typically from env
- **Modular key types** — each format is a small class validating the decoded bytes; ship your own by implementing one interface
- **Fail-fast validation** — wrong length, bad encoding, missing value → `InvalidKeyException` with the key name in the message
- **Interface-first** — consumers depend on `KeyProviderInterface`, not on the concrete storage
- **Memoization** — decode and validation happen once per key per instance
- **No ext requirements** — key-type byte lengths are hardcoded, so describing sodium keys does not require ext-sodium

## Requirements

- PHP >= 8.0
- yiisoft/yii2 ~2.0

## Installation

```bash
composer require tuzelko/yii2-key-storage
```

## Quick start

Register the storage in the DI container under the interface, so consumers never know the concrete class:

```php
// config/main.php
use tuzelko\yii\keystorage\KeyProviderInterface;
use tuzelko\yii\keystorage\KeyStorage;
use tuzelko\yii\keystorage\types\SodiumSecretboxKey;

'container' => [
    'singletons' => [
        KeyProviderInterface::class => static fn () => new KeyStorage([
            'keys' => [
                'appCrypto' => [
                    'base64' => getenv('APP_CRYPTO_KEY'),
                    'type'   => SodiumSecretboxKey::class,
                ],
            ],
        ]),
    ],
],
```

```php
$rawKey = Yii::$container->get(KeyProviderInterface::class)->getRaw('appCrypto');
```

`getRaw()` returns raw binary bytes, ready for `sodium_*` / `hash_hmac` / `openssl_*` calls.

## Key configuration

Each entry under `keys` is `name => descriptor`. A descriptor has exactly one source encoding and a mandatory type:

| Field    | Description                                                             |
|----------|-------------------------------------------------------------------------|
| `base64` | Base64-encoded key value (standard and url-safe alphabets accepted)     |
| `hex`    | Hex-encoded key value                                                   |
| `type`   | `KeyTypeInterface` class name or instance — validates the decoded bytes |

```php
'keys' => [
    'appCrypto'  => ['base64' => getenv('CRYPTO_KEY'),       'type' => SodiumSecretboxKey::class],
    'requestSigning' => ['hex'    => getenv('SIGNING_KEY_HEX'),  'type' => Ed25519SecretKey::class],
    'legacyAes'      => ['base64' => getenv('LEGACY_KEY'),       'type' => new CustomLengthKey(16)],
],
```

## Bundled key types

| Type                 | Valid length | For                                                  |
|----------------------|--------------|------------------------------------------------------|
| `SodiumSecretboxKey` | 32 bytes     | `sodium_crypto_secretbox` (XSalsa20-Poly1305)        |
| `Ed25519PublicKey`   | 32 bytes     | `sodium_crypto_sign` verification                    |
| `Ed25519SecretKey`   | 64 bytes     | `sodium_crypto_sign` signing                         |
| `CustomLengthKey(n)` | `n` bytes    | any fixed-length format not shipped with the package |

## Custom key types

A key type is any class implementing `KeyTypeInterface` — one method, full control over what "valid" means:

```php
use tuzelko\yii\keystorage\InvalidKeyException;
use tuzelko\yii\keystorage\types\KeyTypeInterface;

class PemRsaPrivateKey implements KeyTypeInterface
{
    public function validate(string $raw): void
    {
        if (openssl_pkey_get_private($raw) === false) {
            throw new InvalidKeyException('not a valid PEM RSA private key.');
        }
    }
}
```

For plain length checks extend `FixedLengthKey` instead and implement only `length(): int`.

## Docker secrets

Keys are always supplied as text via env. If you use Docker secrets, deliver them to env in your entrypoint (the common `*_FILE` pattern) — the storage intentionally does not read files, so there is exactly one secret-loading mechanism and no binary-vs-text file pitfalls.

## Error handling

Every failure throws `tuzelko\yii\keystorage\InvalidKeyException` (extends `RuntimeException`) with the key name in the message:

| Condition               | Message                                                   |
|-------------------------|-----------------------------------------------------------|
| Unknown key name        | `Key "x" is not configured.`                              |
| Empty / unset env value | `Key "x": base64 value is empty or not set.`              |
| Bad encoding            | `Key "x": invalid base64 string.` / `invalid hex string.` |
| Missing source          | `Key "x" must specify one of: base64, hex.`               |
| Missing / wrong type    | `Key "x" has unknown or missing type; ...`                |
| Failed type validation  | `Key "x": wrong length: expected 32 bytes, got 16.`       |

## Running tests

```bash
make test
```

Tests run inside Docker (PHP 8.3) with no local setup required.

## License

MIT — see [LICENSE](LICENSE).