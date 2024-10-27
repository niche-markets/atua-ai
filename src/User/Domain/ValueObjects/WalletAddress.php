<?php

declare(strict_types=1);

namespace User\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class WalletAddress implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "wallet_address", unique: true)]
    public readonly string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(string $value)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @throws InvalidValueException
     */
    private function ensureValueIsValid(string $value): void
    {
        if (!$this->isValidEthereumAddress($value) && !$this->isValidSolanaAddress($value)) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>. It must be a valid Ethereum or Solana address.',
                static::class,
                $value
            ));
        }
    }

    private function isValidEthereumAddress(string $address): bool
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    private function isValidSolanaAddress(string $address): bool
    {
        // Solana addresses are base58 encoded and can be between 32 to 44 characters long.
        return preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address) === 1;
    }
}
