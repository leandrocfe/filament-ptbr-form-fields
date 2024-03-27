<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Money extends TextInput
{
    protected string|int|float|null $initialValue = '0,00';

    protected function setUp(): void
    {
        $this
            ->prefix('R$')
            ->maxLength(13)
            ->extraAlpineAttributes([

                'x-on:keypress' => 'function() {
                        var charCode = event.keyCode || event.which;
                        if (charCode < 48 || charCode > 57) {
                            event.preventDefault();
                            return false;
                        }
                        return true;                            
                    }',

                'x-on:keyup' => 'function() {
                        var money = $el.value;
                        money = money.replace(/\D/g, \'\');
                        money = (parseFloat(money) / 100).toLocaleString(\'pt-BR\', { minimumFractionDigits: 2 });
                        $el.value = money === \'NaN\' ? \'0,00\' : money;
                    }',
            ])
            ->dehydrateMask()
            ->default(0.00)
            ->formatStateUsing(fn ($state) => $state ? number_format(floatval($state), 2, ',', '.') : $this->initialValue);
    }

    public function dehydrateMask(bool|Closure $condition = true): static
    {
        if ($condition) {
            $this->dehydrateStateUsing(fn (?string $state): ?float => $this->convertToFloat($state));
        } else {
            $this->dehydrateStateUsing(fn (?string $state): ?string => $this->convertToNumberFormat($state));
        }

        return $this;
    }

    public function initialValue(null|string|int|float|Closure $value = '0,00'): static
    {
        $this->initialValue = $value;

        return $this;
    }

    private function sanitizeState(?string $state): ?Stringable
    {
        $state = Str::of($state)
            ->replace('.', '')
            ->replace(',', '');

        return $state ?? null;
    }

    private function convertToFloat(Stringable|string|null $state): float
    {
        $state = $this->sanitizeState($state);

        if (! $state) {
            return 0;
        }

        if ($state->length() > 2) {
            $state = $state
                ->substr(0, $state->length() - 2)
                ->append('.')
                ->append($state->substr($state->length() - 2, 2));
        } else {
            $state = $state->prepend('0.');
        }

        return floatval($state->toString()) ?? 0;
    }

    private function convertToNumberFormat(string $state): string
    {
        $state = $this->convertToFloat($state);

        return number_format($state, 2, ',', '.') ?? 0;
    }
}
