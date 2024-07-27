<?php

declare(strict_types=1);

namespace Leandrocfe\FilamentPtbrFormFields\Currencies;

use ArchTech\Money\Currency;

class BRL extends Currency
{
    public string $code = 'BRL';

    public string $name = 'Real Brasileiro';

    public string $prefix = '';

    public string $locale = 'pt-BR';

    public string $decimalSeparator = ',';

    public string $thousandsSeparator = '.';
}
