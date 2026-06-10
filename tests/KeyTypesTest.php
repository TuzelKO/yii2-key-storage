<?php

namespace tuzelko\yii\keystorage\tests;

use PHPUnit\Framework\TestCase;
use tuzelko\yii\keystorage\InvalidKeyException;
use tuzelko\yii\keystorage\types\CustomLengthKey;
use tuzelko\yii\keystorage\types\Ed25519PublicKey;
use tuzelko\yii\keystorage\types\Ed25519SecretKey;
use tuzelko\yii\keystorage\types\SodiumSecretboxKey;

class KeyTypesTest extends TestCase
{
    public function lengthsProvider(): array
    {
        return [
            'sodium secretbox'  => [new SodiumSecretboxKey(), 32],
            'ed25519 public'    => [new Ed25519PublicKey(), 32],
            'ed25519 secret'    => [new Ed25519SecretKey(), 64],
            'custom length'     => [new CustomLengthKey(16), 16],
        ];
    }

    /**
     * @dataProvider lengthsProvider
     */
    public function testExactLengthIsAccepted(\tuzelko\yii\keystorage\types\FixedLengthKey $type, int $length): void
    {
        $this->assertSame($length, $type->length());

        $type->validate(str_repeat('x', $length));
        $this->addToAssertionCount(1); // no exception thrown
    }

    /**
     * @dataProvider lengthsProvider
     */
    public function testWrongLengthIsRejected(\tuzelko\yii\keystorage\types\FixedLengthKey $type, int $length): void
    {
        $this->expectException(InvalidKeyException::class);
        $type->validate(str_repeat('x', $length + 1));
    }

    public function testHardcodedLengthsMatchSodiumConstants(): void
    {
        // Lengths are hardcoded to avoid an ext-sodium requirement; this guards against drift.
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('ext-sodium is not available.');
        }

        $this->assertSame(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, (new SodiumSecretboxKey())->length());
        $this->assertSame(SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, (new Ed25519PublicKey())->length());
        $this->assertSame(SODIUM_CRYPTO_SIGN_SECRETKEYBYTES, (new Ed25519SecretKey())->length());
    }
}