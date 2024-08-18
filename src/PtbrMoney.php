<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

/**
 * @deprecated Use `Money` instead.
 */
class PtbrMoney extends TextInput
{
    protected string|int|float|null $initialValue = '0,00';

    protected function setUp(): void
    {
        $this
            ->prefix('R$')
            ->maxLength(13)
            ->extraAlpineAttributes([
                'x-data' => '{ formatMoney(value) {
                    value = value.replace(/\D/g, "");
                    value = (value / 100).toFixed(2) + "";
                    value = value.replace(".", ",");
                    value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
                    value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
                    return value;
                }}',

                'x-on:keypress' => 'function(event) {
                    var charCode = event.keyCode || event.which;
                    if (charCode < 48 || charCode > 57) {
                        event.preventDefault();
                    }
                }',

                'x-on:keyup' => 'function() {
                    $el.value = this.formatMoney($el.value);
                }',

                'x-init' => 'function() {
                    $el.value = this.formatMoney($el.value);
                }'
            ])
            ->dehydrateMask()
            ->default(0.00)
            ->formatStateUsing(fn ($state) => $state ? number_format(floatval($state), 2, ',', '.') : $this->initialValue);
    }

    public function dehydrateMask(bool|Closure $condition = true): static
    {
        if ($condition) {
            $this->dehydrateStateUsing(
                fn ($state): ?float => $state ?
                    floatval(
                        Str::of($state)
                            ->replace('.', '')
                            ->replace(',', '.')
                            ->toString()
                    ) :
                    null
            );
        } else {
            $this->dehydrateStateUsing(null);
        }

        return $this;
    }

    public function initialValue(null|string|int|float|Closure $value = '0,00'): static
    {
        $this->initialValue = $value;

        return $this;
    }
}
