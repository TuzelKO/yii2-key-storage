<?php

namespace tuzelko\yii\keystorage;

/**
 * Contract for resolving raw key bytes by name.
 *
 * Consumers (behaviors, signers, encryptors) should depend on this interface —
 * typically via the DI container — rather than on a concrete storage:
 *
 *   $provider = Yii::$container->get(KeyProviderInterface::class);
 *   $rawKey   = $provider->getRaw('appCrypto');
 */
interface KeyProviderInterface
{
    /**
     * Returns raw (binary) key bytes for the given key name.
     *
     * @throws InvalidKeyException when the key is unknown, malformed, or fails validation.
     */
    public function getRaw(string $name): string;
}