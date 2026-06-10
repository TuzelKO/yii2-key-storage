<?php

namespace tuzelko\yii\keystorage\types;

/**
 * Ed25519 public key (sodium_crypto_sign).
 *
 * Length is hardcoded (SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES = 32) so this package
 * does not require ext-sodium just to describe key formats.
 */
class Ed25519PublicKey extends FixedLengthKey
{
    public function length(): int
    {
        return 32;
    }
}