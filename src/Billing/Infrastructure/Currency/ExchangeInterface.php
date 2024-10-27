<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency;

use Billing\Domain\ValueObjects\Price;
use Shared\Domain\ValueObjects\CurrencyCode;

interface ExchangeInterface
{
    public function convert(
        Price $amount,
        CurrencyCode $from,
        CurrencyCode $to
    ): Price;
}
