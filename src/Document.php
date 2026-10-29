<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class Document extends TextInput
{
    public const CPF_MASK = '999.999.999-99';

    public const CNPJ_MASK = '99.999.999/9999-99';

    public const CNPJ_ALPHANUMERIC_MASK = '**.***.***/****-99';

    public bool $validation = true;

    protected bool $dehydrateMask = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(fn (mixed $state): mixed => $this->toUpperCase($state));
    }

    /**
     * The alphanumeric CNPJ (IN RFB nº 2.119/2022) only assigns values to
     * uppercase letters, so every state leaving this field is normalized.
     */
    protected function toUpperCase(mixed $state): mixed
    {
        return is_string($state) ? strtoupper($state) : $state;
    }

    /**
     * @return array<mixed>
     */
    public function getExtraInputAttributes(): array
    {
        return [
            'style' => 'text-transform: uppercase',
            ...parent::getExtraInputAttributes(),
        ];
    }

    public function mutateStateForValidation(mixed $state): mixed
    {
        return $this->toUpperCase(parent::mutateStateForValidation($state));
    }

    public function mutatesStateForValidation(): bool
    {
        return true;
    }

    public function mutateDehydratedState(mixed $state): mixed
    {
        $state = $this->toUpperCase(parent::mutateDehydratedState($state));

        if (! $this->dehydrateMask || ! is_string($state)) {
            return $state;
        }

        return preg_replace('/[^A-Z0-9]/', '', $state);
    }

    public function mutatesDehydratedState(): bool
    {
        return true;
    }

    public function dehydrateMask(bool $condition = true): static
    {
        $this->dehydrateMask = $condition;

        return $this;
    }

    public function dynamic(bool $condition = true, bool $alphanumeric = true): static
    {
        if (self::getValidation()) {
            $this->rule('cpf_ou_cnpj');
        }

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

        if (self::getValidation()) {
            $this->rule('cpf');
        }

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
