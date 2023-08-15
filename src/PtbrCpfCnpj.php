<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

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
                $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
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

    public function cnpj(string|Closure $format = '99.999.999/9999-99'): static
    {
        $this->dynamic(false)
            ->mask($format);

        return $this;
    }
}
