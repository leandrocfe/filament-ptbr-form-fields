<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class Document extends TextInput
{
    public bool $validation = true;

    public function dynamic(bool $condition = true): static
    {
        if (self::getValidation()) {
            $this->rule('cpf_ou_cnpj');
        }

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

        if (self::getValidation()) {
            $this->rule('cpf');
        }

        return $this;
    }

    public function cnpj(string|Closure $format = '99.999.999/9999-99'): static
    {
        $this->dynamic(false)
            ->mask($format);

        if (self::getValidation()) {
            $this->rule('cnpj');
        }

        return $this;
    }

    public function validation(bool|Closure $condition = true): static
    {
        $this->validation = $condition;

        return $this;
    }

    public function getValidation(): bool
    {
        return $this->validation;
    }
}
