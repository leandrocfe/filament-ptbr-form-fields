<?php

declare(strict_types=1);

namespace Leandrocfe\FilamentPtbrFormFields\Currencies;

use ArchTech\Money\Currency;

class USD extends Currency
{
    public string $code = 'USD';

    public string $name = 'United States Dollar';

    public float $rate = 1.0;

    public int $mathDecimals = 2;

    public int $displayDecimals = 2;

    public int $rounding = 2;

    public string $prefix = '';

    public string $locale = 'en';

    public string $decimalSeparator = '.';

    public string $thousandsSeparator = ',';
}
