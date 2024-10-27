<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency;

use Billing\Domain\ValueObjects\Price;
use Shared\Domain\ValueObjects\CurrencyCode;
use Symfony\Component\Intl\Currencies;

class Exchange implements ExchangeInterface
{
    public function __construct(
        private RateProviderInterface $provider
    ) {
    }

    public function convert(
        Price $amount,
        CurrencyCode $from,
        CurrencyCode $to
    ): Price {
        if ($from->value === $to->value) {
            return $amount;
        }

        $rate = $this->provider->getRate($from, $to);

        $amount = $amount->value / (10 ** Currencies::getFractionDigits($from->value));
        $convertedAmount =  $rate * $amount * (10 ** Currencies::getFractionDigits($to->value));

        return new Price((int) round($convertedAmount));
    }
}
