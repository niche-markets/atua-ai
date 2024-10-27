<?php

namespace Billing\Infrastructure\Currency\RateProviders;

use Billing\Infrastructure\Currency\RateProviderInterface;
use RuntimeException;
use Shared\Domain\ValueObjects\CurrencyCode;
use Shared\Infrastructure\Atributes\BuiltInAspect;

#[BuiltInAspect]
class NullRateProvider implements RateProviderInterface
{
    public const LOOKUP_KEY = 'nrp';

    public function getName(): string
    {
        return 'Null Rate Provider';
    }

    public function getRate(CurrencyCode $from, CurrencyCode $to): int|float
    {
        throw new RuntimeException('Rate provider not found');
    }
}
