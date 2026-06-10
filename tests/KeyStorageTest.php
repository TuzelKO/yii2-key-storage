<?php

namespace tuzelko\yii\keystorage\tests;

use PHPUnit\Framework\TestCase;
use tuzelko\yii\keystorage\InvalidKeyException;
use tuzelko\yii\keystorage\KeyProviderInterface;
use tuzelko\yii\keystorage\KeyStorage;
use tuzelko\yii\keystorage\types\CustomLengthKey;
use tuzelko\yii\keystorage\types\SodiumSecretboxKey;

class KeyStorageTest extends TestCase
{
    private const RAW_32 = '0123456789abcdef0123456789abcdef';

    private function storage(array $keys): KeyStorage
    {
        return new KeyStorage(['keys' => $keys]);
    }

    // -------------------------------------------------------------------------
    // Decoding
    // -------------------------------------------------------------------------

    public function testBase64KeyIsDecoded(): void
    {
        $storage = $this->storage([
            'main' => ['base64' => base64_encode(self::RAW_32), 'type' => SodiumSecretboxKey::class],
        ]);

        $this->assertSame(self::RAW_32, $storage->getRaw('main'));
    }

    public function testUrlSafeBase64IsAccepted(): void
    {
        // 0xfb 0xff 0xfe ... produces '+' and '/' in standard base64
        $raw = "\xfb\xff\xfe" . substr(self::RAW_32, 3);
        $urlSafe = strtr(base64_encode($raw), '+/', '-_');

        $storage = $this->storage([
            'main' => ['base64' => $urlSafe, 'type' => SodiumSecretboxKey::class],
        ]);

        $this->assertSame($raw, $storage->getRaw('main'));
    }

    public function testHexKeyIsDecoded(): void
    {
        $storage = $this->storage([
            'main' => ['hex' => bin2hex(self::RAW_32), 'type' => SodiumSecretboxKey::class],
        ]);

        $this->assertSame(self::RAW_32, $storage->getRaw('main'));
    }

    public function testResolvedKeyIsMemoized(): void
    {
        $storage = $this->storage([
            'main' => ['base64' => base64_encode(self::RAW_32), 'type' => SodiumSecretboxKey::class],
        ]);

        $this->assertSame(self::RAW_32, $storage->getRaw('main'));

        // Source mutation after the first access must not affect the resolved value.
        $storage->keys['main']['base64'] = base64_encode(strrev(self::RAW_32));
        $this->assertSame(self::RAW_32, $storage->getRaw('main'));
    }

    // -------------------------------------------------------------------------
    // Key types
    // -------------------------------------------------------------------------

    public function testTypeAsInstanceIsAccepted(): void
    {
        $storage = $this->storage([
            'short' => ['hex' => bin2hex('0123456789abcdef'), 'type' => new CustomLengthKey(16)],
        ]);

        $this->assertSame('0123456789abcdef', $storage->getRaw('short'));
    }

    public function testWrongLengthIsRejectedWithKeyNameInMessage(): void
    {
        $storage = $this->storage([
            'main' => ['base64' => base64_encode('too-short'), 'type' => SodiumSecretboxKey::class],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('Key "main": wrong length: expected 32 bytes, got 9.');
        $storage->getRaw('main');
    }

    public function testMissingTypeIsRejected(): void
    {
        $storage = $this->storage([
            'main' => ['base64' => base64_encode(self::RAW_32)],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('unknown or missing type');
        $storage->getRaw('main');
    }

    public function testNonKeyTypeClassNameIsRejected(): void
    {
        $storage = $this->storage([
            'main' => ['base64' => base64_encode(self::RAW_32), 'type' => \stdClass::class],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('unknown or missing type');
        $storage->getRaw('main');
    }

    // -------------------------------------------------------------------------
    // Errors
    // -------------------------------------------------------------------------

    public function testUnknownKeyNameIsRejected(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('Key "nope" is not configured.');
        $this->storage([])->getRaw('nope');
    }

    public function testEmptyBase64ValueIsRejected(): void
    {
        // getenv() returns false for unset variables — must be reported as "empty", not decoded.
        $storage = $this->storage([
            'main' => ['base64' => false, 'type' => SodiumSecretboxKey::class],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('base64 value is empty or not set');
        $storage->getRaw('main');
    }

    public function testInvalidBase64IsRejected(): void
    {
        $storage = $this->storage([
            'main' => ['base64' => '%%%not-base64%%%', 'type' => SodiumSecretboxKey::class],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('invalid base64 string');
        $storage->getRaw('main');
    }

    public function testOddLengthHexIsRejected(): void
    {
        $storage = $this->storage([
            'main' => ['hex' => 'abc', 'type' => SodiumSecretboxKey::class],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('invalid hex string');
        $storage->getRaw('main');
    }

    public function testNonHexCharactersAreRejected(): void
    {
        $storage = $this->storage([
            'main' => ['hex' => 'zz00', 'type' => SodiumSecretboxKey::class],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('invalid hex string');
        $storage->getRaw('main');
    }

    public function testMissingSourceIsRejected(): void
    {
        $storage = $this->storage([
            'main' => ['type' => SodiumSecretboxKey::class],
        ]);

        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('must specify one of: base64, hex');
        $storage->getRaw('main');
    }

    // -------------------------------------------------------------------------
    // Contract
    // -------------------------------------------------------------------------

    public function testStorageImplementsProviderInterface(): void
    {
        $this->assertInstanceOf(KeyProviderInterface::class, $this->storage([]));
    }
}