<?php

namespace tuzelko\yii\keystorage\types;

use tuzelko\yii\keystorage\InvalidKeyException;

/**
 * Base class for key types whose only invariant is an exact byte length.
 */
abstract class FixedLengthKey implements KeyTypeInterface
{
    /**
     * Expected raw key length in bytes.
     */
    abstract public function length(): int;

    public function validate(string $raw): void
    {
        if (strlen($raw) !== $this->length()) {
            throw new InvalidKeyException(
                sprintf('wrong length: expected %d bytes, got %d.', $this->length(), strlen($raw))
            );
        }
    }
}