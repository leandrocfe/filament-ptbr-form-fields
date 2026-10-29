<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

/**
 * @deprecated Use `Document` instead.
 */
class PtbrCpfCnpj extends TextInput
{
    public const CPF_MASK = '999.999.999-99';

    public const CNPJ_MASK = '99.999.999/9999-99';

    public const CNPJ_ALPHANUMERIC_MASK = '**.***.***/****-99';

    protected function setUp(): void
    {
        $this->dynamic();
    }

    public function dynamic(bool $condition = true, bool $alphanumeric = true): static
    {
        if ($condition) {
            $cnpjMask = $alphanumeric ? self::CNPJ_ALPHANUMERIC_MASK : self::CNPJ_MASK;
            $cpfMask = self::CPF_MASK;

            $js = $alphanumeric
                ? "/[A-Za-z]/.test(\$input) || \$input.replace(/[^A-Za-z0-9]/g, '').length > 11 ? '{$cnpjMask}' : '{$cpfMask}'"
                : "\$input.replace(/\\D/g, '').length > 11 ? '{$cnpjMask}' : '{$cpfMask}'";

            $this->mask(RawJs::make($js))->minLength(14);
        }

        return $this;
    }

    public function cpf(string|Closure $format = self::CPF_MASK): static
    {
        $this->dynamic(false)
            ->mask($format);

        return $this;
    }

    public function cnpj(string|Closure|bool|null $format = null, bool $alphanumeric = true): static
    {
        if (is_bool($format)) {
            $alphanumeric = $format;
            $format = null;
        }

        $format ??= $alphanumeric ? self::CNPJ_ALPHANUMERIC_MASK : self::CNPJ_MASK;

        $this->dynamic(false)
            ->mask($format);

        return $this;
    }
}
