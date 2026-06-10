<?php

namespace tuzelko\yii\keystorage;

use RuntimeException;

/**
 * Thrown when a key is unknown, has no usable source, fails to decode,
 * or does not pass its key-type validation.
 */
class InvalidKeyException extends RuntimeException
{
}