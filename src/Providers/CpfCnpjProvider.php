<?php

namespace Leandrocfe\FilamentPtbrFormFields\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reference provider that fills document data from the cpfcnpj.com.br API.
 *
 * The token and the package are read from the configuration or the
 * environment, so the field keeps its current behavior when neither is set.
 */
class CpfCnpjProvider implements DocumentProviderInterface
{
    protected ?string $url;

    protected ?string $token;

    protected int|string|null $package;

    public function __construct(?string $token = null, int|string|null $package = null, ?string $url = null)
    {
        $this->token = $token ?? config('filament-ptbr-form-fields.cpfcnpj_token');
        $this->package = $package ?? config('filament-ptbr-form-fields.cpfcnpj_package');
        $this->url = $url ?? config('filament-ptbr-form-fields.cpfcnpj_url');
    }

    public function fetch(string $document): null|Collection|array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $document = Str::upper((string) preg_replace('/[^0-9A-Za-z]/', '', $document));

        if (! in_array(strlen($document), [11, 14], true)) {
            return null;
        }

        $url = Str::of((string) $this->url)
            ->finish('/')
            ->append((string) $this->token)
            ->append('/')
            ->append((string) $this->resolvePackage($document))
            ->append('/')
            ->append($document);

        try {
            $response = Http::timeout(5)->get((string) $url)->json();
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($response)) {
            return null;
        }

        if (blank($response) || Arr::has($response, 'erro') || Arr::has($response, 'error')) {
            return null;
        }

        if (Arr::has($response, 'status') && ! Arr::get($response, 'status')) {
            return null;
        }

        return $response;
    }

    public function isEnabled(): bool
    {
        return filled($this->token) && filled($this->url);
    }

    /**
     * Return the configured package or infer it from the document length.
     *
     * A document with 11 characters is treated as a CPF (package 3) and any
     * other length is treated as a CNPJ (package 6).
     */
    protected function resolvePackage(string $document): int|string
    {
        if (filled($this->package)) {
            return $this->package;
        }

        return strlen($document) === 11 ? 3 : 6;
    }
}
