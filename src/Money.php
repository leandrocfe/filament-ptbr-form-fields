<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use ArchTech\Money\Currency;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Leandrocfe\FilamentPtbrFormFields\Currencies\BRL;

class Money extends TextInput
{
    protected string|int|float|null $initialValue = '0,00';

    protected ?Currency $currency = null;

    protected bool|Closure $dehydrateMask = false;

    protected bool|Closure $intFormat = false;

    protected function setUp(): void
    {
        $this
            ->currency()
            ->prefix('R$')
            ->extraAlpineAttributes(fn () => $this->getOnKeyPress())
            ->extraAlpineAttributes(fn () => $this->getOnKeyUp())
            ->formatStateUsing(fn ($state) => $this->hydrateCurrency($state))
            ->dehydrateStateUsing(fn ($state) => $this->dehydrateCurrency($state));
    }

    public function initialValue(null|string|int|float|Closure $value = '0,00'): static
    {
        $this->initialValue = $value;

        return $this;
    }

    public function currency(string|null|Closure $currency = BRL::class): static
    {
        $this->currency = new ($currency);
        currencies()->add($currency);

        if ($currency !== 'BRL') {
            $this->prefix(null);
        }

        return $this;
    }

    protected function hydrateCurrency($state): string
    {
        $sanitized = $this->sanitizeState($state);

        $money = money(amount: $sanitized, currency: $this->getCurrency());

        return $money->formatted(prefix: '');
    }

    protected function dehydrateCurrency($state): int|float|string
    {
        $sanitized = $this->sanitizeState($state);
        $money = money(amount: $sanitized, currency: $this->getCurrency());

        if ($this->getDehydrateMask()) {
            return $money->formatted();
        }

        return $this->getIntFormat() ? $money->value() : $money->decimal();
    }

    public function dehydrateMask(bool $condition = true): static
    {
        $this->dehydrateMask = $condition;

        return $this;
    }

    public function intFormat(bool|Closure $intFormat = true): static
    {
        $this->intFormat = $intFormat;

        return $this;
    }

    protected function sanitizeState(?string $state): ?int
    {
        $state = Str::of($state)
            ->replace('.', '')
            ->replace(',', '')
            ->toInteger();

        return $state;
    }

    protected function getOnKeyPress(): array
    {
        return [
            'x-on:keypress' => 'function() {
                var charCode = event.keyCode || event.which;
                if (charCode < 48 || charCode > 57) {
                    event.preventDefault();
                    return false;
                }
                return true;
            }',
        ];
    }

    protected function getOnKeyUp(): array
    {
        $currency = new ($this->getCurrency());
        $numberFormatter = $currency->locale;

        return [
            'x-on:keyup' => 'function() {
                $el.value = Currency.masking($el.value, {locales:\''.$numberFormatter.'\'});
            }',
        ];
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function getDehydrateMask(): bool
    {
        return $this->dehydrateMask;
    }

    public function getIntFormat(): bool
    {
        return $this->intFormat;
    }
}
