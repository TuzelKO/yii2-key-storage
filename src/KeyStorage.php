<?php

namespace tuzelko\yii\keystorage;

use tuzelko\yii\keystorage\types\KeyTypeInterface;
use yii\base\Component;

/**
 * Application component for loading and validating named cryptographic keys.
 *
 * Each key is configured with exactly one source encoding ('base64' or 'hex')
 * and a mandatory 'type' — a KeyTypeInterface implementation (class name or
 * instance) that validates the decoded bytes.
 *
 * Keys are supplied as text encodings (typically from env). Docker secrets
 * should be delivered to env by the entrypoint (`*_FILE` pattern) — the storage
 * itself does not read files, avoiding a duplicate secret-loading mechanism and
 * the binary-vs-text pitfalls of reading raw bytes from a file.
 *
 * Registration (DI container, so consumers can depend on the interface):
 *
 *   'container' => [
 *       'singletons' => [
 *           KeyProviderInterface::class => static fn () => new KeyStorage([
 *               'keys' => [
 *                   'appCrypto' => [
 *                       'base64' => getenv('APP_CRYPTO_KEY'),
 *                       'type'   => SodiumSecretboxKey::class,
 *                   ],
 *               ],
 *           ]),
 *       ],
 *   ],
 *
 * Usage:
 *
 *   $rawKey = Yii::$container->get(KeyProviderInterface::class)->getRaw('appCrypto');
 */
class KeyStorage extends Component implements KeyProviderInterface
{
    /**
     * Map of key name => source descriptor.
     * Each entry must contain exactly one of: 'base64', 'hex', plus a mandatory 'type'.
     *
     * @var array<string, array{type: KeyTypeInterface|class-string<KeyTypeInterface>, base64?: string|false, hex?: string|false}>
     */
    public array $keys = [];

    /** @var array<string, string> Decoded key bytes, populated on first access. */
    private array $resolved = [];

    /**
     * Returns raw key bytes for $name.
     * Decodes and validates on first call, returns cached bytes on subsequent calls.
     *
     * @throws InvalidKeyException on unknown name, empty value, decode failure, or failed validation.
     */
    public function getRaw(string $name): string
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (!isset($this->keys[$name])) {
            throw new InvalidKeyException("Key \"$name\" is not configured.");
        }

        $def = $this->keys[$name];
        $raw = $this->decode($name, $def);

        try {
            $this->type($name, $def)->validate($raw);
        } catch (InvalidKeyException $e) {
            throw new InvalidKeyException("Key \"$name\": " . $e->getMessage(), 0, $e);
        }

        return $this->resolved[$name] = $raw;
    }

    /**
     * Resolves the 'type' descriptor entry into a KeyTypeInterface instance.
     *
     * @param array{type?: mixed} $def
     * @throws InvalidKeyException
     */
    private function type(string $name, array $def): KeyTypeInterface
    {
        $type = $def['type'] ?? null;

        if ($type instanceof KeyTypeInterface) {
            return $type;
        }

        if (is_string($type) && is_subclass_of($type, KeyTypeInterface::class)) {
            return new $type();
        }

        throw new InvalidKeyException(
            "Key \"$name\" has unknown or missing type; expected a " . KeyTypeInterface::class . ' instance or class name.'
        );
    }

    /**
     * @param array{base64?: string|false, hex?: string|false} $def
     * @throws InvalidKeyException
     */
    private function decode(string $name, array $def): string
    {
        if (array_key_exists('base64', $def)) {
            $value = $def['base64'];
            if (empty($value)) {
                throw new InvalidKeyException("Key \"$name\": base64 value is empty or not set.");
            }
            // Accept both standard and url-safe base64 alphabets.
            $raw = base64_decode(strtr((string) $value, '-_', '+/'), true);
            if ($raw === false) {
                throw new InvalidKeyException("Key \"$name\": invalid base64 string.");
            }
            return $raw;
        }

        if (array_key_exists('hex', $def)) {
            $value = (string) $def['hex'];
            if ($value === '') {
                throw new InvalidKeyException("Key \"$name\": hex value is empty or not set.");
            }
            // Validate before hex2bin(): on a bad string it raises a PHP warning that Yii turns into
            // an ErrorException, so the `=== false` branch would be unreachable otherwise.
            if (strlen($value) % 2 !== 0 || !ctype_xdigit($value)) {
                throw new InvalidKeyException("Key \"$name\": invalid hex string.");
            }
            return hex2bin($value);
        }

        throw new InvalidKeyException("Key \"$name\" must specify one of: base64, hex.");
    }
}