<?php

declare(strict_types=1);

namespace Leandrocfe\FilamentPtbrFormFields\Currencies;

use ArchTech\Money\Currency;

class BRL extends Currency
{
    public string $code = 'BRL';

    public string $name = 'Real Brasileiro';

    public float $rate = 1.0;

    public int $mathDecimals = 2;

    public int $displayDecimals = 2;

    public int $rounding = 2;

    public string $prefix = '';

    public string $locale = 'pt-BR';

    public string $decimalSeparator = ',';

    public string $thousandsSeparator = '.';
}
