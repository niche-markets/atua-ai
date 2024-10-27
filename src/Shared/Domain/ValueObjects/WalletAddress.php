<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class WalletAddress implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "wallet_address", nullable: true)]
    public readonly ?string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(?string $value = null)
    {
        if ($value !== null) {
            $this->ensureValueIsValid($value);
        }
        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }

    /**
     * @throws InvalidValueException
     */
    
    private function ensureValueIsValid(?string $value)
    {
        if (!is_null($value) && !$this->isValidWalletAddress($value)) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>.',
                static::class,
                $value
            ));
        }
    }

    private function isValidWalletAddress(string $value): bool
    {
        // Validate Ethereum address (0x followed by 40 hex characters)
        if (preg_match('/^0x[a-fA-F0-9]{40}$/', $value)) {
            return true;
        }

        // Validate Solana address (44 characters long, base58)
        if (preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $value)) {
            return true;
        }

        return true;
        // return false;
    }
}
