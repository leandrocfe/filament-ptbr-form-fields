<?php

use Illuminate\Support\Facades\Http;
use Leandrocfe\FilamentPtbrFormFields\Providers\BrasilApiProvider;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('fetches address data using default brasilapi configuration', function () {
    $expectedData = [
        'cep' => '01001000',
        'state' => 'SP',
        'city' => 'São Paulo',
        'neighborhood' => 'Sé',
        'street' => 'Praça da Sé',
        'service' => 'viacep',
    ];

    Http::fake([
        'https://brasilapi.com.br/api/cep/v1/01001000' => Http::response($expectedData, 200),
    ]);

    $provider = new BrasilApiProvider;
    $response = $provider->fetch('01001000');

    expect($response)->toBe($expectedData);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://brasilapi.com.br/api/cep/v1/01001000';
    });
});

it('returns null when brasilapi returns error payload', function () {
    Http::fake([
        'https://brasilapi.com.br/api/cep/v1/99999999' => Http::response([
            'name' => 'CepPromiseError',
            'message' => 'CEP não encontrado na base do BrasilAPI',
            'type' => 'service_error',
            'error' => 'not_found',
        ], 404),
    ]);

    $provider = new BrasilApiProvider;
    $response = $provider->fetch('99999999');

    expect($response)->toBeNull();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://brasilapi.com.br/api/cep/v1/99999999';
    });
});

it('returns null when brasilapi returns empty response', function () {
    Http::fake([
        'https://brasilapi.com.br/api/cep/v1/00000000' => Http::response([], 200),
    ]);

    $provider = new BrasilApiProvider;
    $response = $provider->fetch('00000000');

    expect($response)->toBeNull();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://brasilapi.com.br/api/cep/v1/00000000';
    });
});

it('fetches address data using custom url passed to constructor', function () {
    $expectedData = [
        'cep' => '01001000',
        'state' => 'SP',
        'city' => 'São Paulo',
    ];

    Http::fake([
        'https://custom-brasilapi.test/api/cep/v1/01001000' => Http::response($expectedData, 200),
    ]);

    $provider = new BrasilApiProvider('https://custom-brasilapi.test/api/cep/v1/');
    $response = $provider->fetch('01001000');

    expect($response)->toBe($expectedData);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://custom-brasilapi.test/api/cep/v1/01001000';
    });
});

it('fetches address data using overridden config brasilapi_url', function () {
    config()->set('filament-ptbr-form-fields.brasilapi_url', 'https://custom-config-brasilapi.test/api/cep/v1/');

    $expectedData = [
        'cep' => '01001000',
        'state' => 'SP',
        'city' => 'São Paulo',
    ];

    Http::fake([
        'https://custom-config-brasilapi.test/api/cep/v1/01001000' => Http::response($expectedData, 200),
    ]);

    $provider = new BrasilApiProvider;
    $response = $provider->fetch('01001000');

    expect($response)->toBe($expectedData);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://custom-config-brasilapi.test/api/cep/v1/01001000';
    });
});
