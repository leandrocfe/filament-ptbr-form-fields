<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Component as Livewire;

class PtbrCep extends TextInput
{
    public function viaCep(string $mode = 'suffix', string $errorMessage = 'CEP inválido.', string $eventFocus = '', array $setFields = []): static
    {
        $viaCepRequest = function ($state, $livewire, $set, $component, $errorMessage, $eventFocus, array $setFields) {

            $livewire->validateOnly($component->getKey());

            $request = Http::get("viacep.com.br/ws/$state/json/")->json();

            foreach ($setFields as $key => $value) {
                $set($key, $request[$value] ?? null);
            }

            if (Arr::has($request, 'erro')) {
                throw ValidationException::withMessages([
                    $component->getKey() => $errorMessage,
                ]);
            }

            if ($eventFocus) {
                $livewire->dispatch($eventFocus);
            }

        };

        $this
            ->minLength(9)
            ->mask('99999-999')
            ->afterStateUpdated(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $setFields, $eventFocus, $viaCepRequest) {
                $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $eventFocus, $setFields);
            })
            ->suffixAction(function () use ($mode, $errorMessage, $eventFocus, $setFields, $viaCepRequest) {
                if ($mode === 'suffix') {
                    return Action::make('search-action')
                        ->label('Buscar CEP')
                        ->icon('heroicon-o-magnifying-glass')
                        ->action(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $eventFocus, $setFields, $viaCepRequest) {
                            $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $eventFocus, $setFields);
                        })
                        ->cancelParentActions();
                }
            })
            ->prefixAction(function () use ($mode, $errorMessage, $eventFocus, $setFields, $viaCepRequest) {
                if ($mode === 'prefix') {
                    return Action::make('search-action')
                        ->label('Buscar CEP')
                        ->icon('heroicon-o-magnifying-glass')
                        ->action(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $eventFocus, $setFields, $viaCepRequest) {
                            $viaCepRequest($state, $livewire, $set, $component, $errorMessage, $eventFocus, $setFields);
                        })
                        ->cancelParentActions();
                }
            });

        return $this;
    }
}
