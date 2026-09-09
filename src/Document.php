<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class Document extends TextInput
{
    public bool $validation = true;

    protected bool $dehydrateMask = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrateStateUsing(function (?string $state) {
            if (! $this->dehydrateMask || $state === null) {
                return $state;
            }

            return preg_replace('/[^A-Za-z0-9]/', '', $state);
        });
    }

    public function dehydrateMask(bool $condition = true): static
    {
        $this->dehydrateMask = $condition;

        return $this;
    }

    public function dynamic(bool $condition = true): static
    {
        if (self::getValidation()) {
            $this->rule('cpf_ou_cnpj');
        }

        if ($condition) {
            $this->mask(RawJs::make(<<<'JS'
                /[A-Za-z]/.test($input) || $input.replace(/[^A-Za-z0-9]/g, '').length > 11 ? '**.***.***/****-99' : '999.999.999-99'
            JS
            ))->minLength(14);
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

    public function cnpj(string|Closure $format = '**.***.***/****-99'): static
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
