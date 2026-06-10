<?php

namespace tuzelko\yii\keystorage\types;

/**
 * Ed25519 secret key (sodium_crypto_sign).
 *
 * Length is hardcoded (SODIUM_CRYPTO_SIGN_SECRETKEYBYTES = 64) so this package
 * does not require ext-sodium just to describe key formats.
 */
class Ed25519SecretKey extends FixedLengthKey
{
    public function length(): int
    {
        return 64;
    }
}