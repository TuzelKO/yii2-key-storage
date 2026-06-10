<?php

namespace tuzelko\yii\keystorage\types;

use tuzelko\yii\keystorage\InvalidKeyException;

/**
 * Contract for key-type validation.
 *
 * A key type decides whether decoded raw bytes form a valid key of its kind.
 * Implement this interface to support custom key formats — the storage does
 * not need to know about them in advance.
 */
interface KeyTypeInterface
{
    /**
     * Validates raw key bytes.
     *
     * The message should not mention the key name — the storage adds that
     * context when rethrowing.
     *
     * @throws InvalidKeyException when the bytes do not form a valid key.
     */
    public function validate(string $raw): void;
}