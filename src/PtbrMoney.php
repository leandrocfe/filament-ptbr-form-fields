<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class PtbrMoney extends TextInput
{
    protected string|int|float|Closure $defaultValue = '0,00';

    protected function setUp(): void
    {
        $this
            ->prefix('R$')
            ->maxLength(13)
            ->dehydrateMask()
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
                        var money = $el.value.replace(/\D/g, "");
                        money = (money / 100).toFixed(2) + "";
                        money = money.replace(".", ",");
                        money = money.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
                        money = money.replace(/(\d)(\d{3}),/g, "$1.$2,");
                        
                        $el.value = money;
                    }',
            ]);
    }

    public function dehydrateMask(bool|Closure $condition = true): static
    {
        if ($condition) {
            $this
                ->dehydrateStateUsing(
                    fn ($state): null|float => $state ?
                        floatval(
                            Str::of($state)
                                ->replace('.', '')
                                ->replace(',', '.')
                                ->toString()
                        ) :
                        null
                )
                ->formatStateUsing(
                    fn ($state) => $state ?
                        number_format($state, 2, ',', '.') :
                        $this->defaultValue
                );
        }

        return $this;
    }

    public function defaultValue(string|int|float|Closure $value = '0,00'): static
    {
        $this->defaultValue = $value;

        return $this;
    }
}
