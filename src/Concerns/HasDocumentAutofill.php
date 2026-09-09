<?php

namespace Leandrocfe\FilamentPtbrFormFields\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Leandrocfe\FilamentPtbrFormFields\Providers\DocumentProviderInterface;
use Livewire\Component;

trait HasDocumentAutofill
{
    protected null|string|Htmlable $autofillErrorMessage = 'Documento inválido';

    public function autofillErrorMessage(null|string|Htmlable $message): static
    {
        $this->autofillErrorMessage = $message;

        return $this;
    }

    public function getAutofillErrorMessage(): null|string|Htmlable
    {
        return $this->autofillErrorMessage;
    }

    protected function configureAutofill(DocumentProviderInterface $provider, callable $callback): static
    {
        if (! $provider->isEnabled()) {
            return $this;
        }

        return $this
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set, TextInput $component, Component $livewire) use ($provider, $callback) {
                $statePath = $component->getStatePath();

                $livewire->validateOnly($statePath);

                if (blank($state) || $livewire->getErrorBag()->has($statePath)) {
                    return;
                }

                $response = $this->fetchDocumentData($state, $provider);

                if (blank($response)) {
                    $livewire->addError($statePath, $this->getAutofillErrorMessage());
                }

                $callback($set, $response);
            });
    }

    protected function fetchDocumentData(?string $document, DocumentProviderInterface $provider): null|array|Collection
    {
        if (blank($document)) {
            return null;
        }

        return $provider->fetch($document);
    }
}
