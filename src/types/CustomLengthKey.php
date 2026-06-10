<?php

namespace tuzelko\yii\keystorage\types;

/**
 * Key type with an arbitrary fixed length, for formats not shipped with
 * the package:
 *
 *   'type' => new CustomLengthKey(16),
 */
class CustomLengthKey extends FixedLengthKey
{
    public function __construct(private int $length)
    {
    }

    public function length(): int
    {
        return $this->length;
    }
}