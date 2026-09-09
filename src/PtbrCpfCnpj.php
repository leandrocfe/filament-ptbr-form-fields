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
    protected function setUp(): void
    {
        $this->dynamic();
    }

    public function dynamic(bool $condition = true): static
    {
        if ($condition) {
            $this->mask(RawJs::make(<<<'JS'
                /[A-Za-z]/.test($input) || $input.replace(/[^A-Za-z0-9]/g, '').length > 11 ? '**.***.***/****-99' : '999.999.999-99'
            JS))->minLength(14);
        }

        return $this;
    }

    public function cpf(string|Closure $format = '999.999.999-99'): static
    {
        $this->dynamic(false)
            ->mask($format);

        return $this;
    }

    public function cnpj(string|Closure $format = '**.***.***/****-99'): static
    {
        $this->dynamic(false)
            ->mask($format);

        return $this;
    }
}
