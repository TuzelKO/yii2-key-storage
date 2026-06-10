<?php

namespace tuzelko\yii\keystorage\types;

/**
 * XSalsa20-Poly1305 secretbox key (sodium_crypto_secretbox).
 *
 * Length is hardcoded (SODIUM_CRYPTO_SECRETBOX_KEYBYTES = 32) so this package
 * does not require ext-sodium just to describe key formats.
 */
class SodiumSecretboxKey extends FixedLengthKey
{
    public function length(): int
    {
        return 32;
    }
}