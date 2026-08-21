<?php

use Illuminate\Support\Facades\Http;
use Leandrocfe\FilamentPtbrFormFields\Providers\ViaCepProvider;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('fetches address data using default viacep configuration', function () {
    $expectedData = [
        'cep' => '01001-000',
        'logradouro' => 'Praça da Sé',
        'complemento' => 'lado ímpar',
        'bairro' => 'Sé',
        'localidade' => 'São Paulo',
        'uf' => 'SP',
        'ibge' => '3550308',
        'gia' => '1004',
        'ddd' => '11',
        'siafi' => '7107',
    ];

    Http::fake([
        'https://viacep.com.br/ws/01001000/json/' => Http::response($expectedData, 200),
    ]);

    $provider = new ViaCepProvider;
    $response = $provider->fetch('01001000');

    expect($response)->toBe($expectedData);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://viacep.com.br/ws/01001000/json/';
    });
});

it('returns null when viacep returns erro payload', function () {
    Http::fake([
        'https://viacep.com.br/ws/99999999/json/' => Http::response(['erro' => 'true'], 200),
    ]);

    $provider = new ViaCepProvider;
    $response = $provider->fetch('99999999');

    expect($response)->toBeNull();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://viacep.com.br/ws/99999999/json/';
    });
});

it('returns null when viacep returns empty response', function () {
    Http::fake([
        'https://viacep.com.br/ws/00000000/json/' => Http::response([], 200),
    ]);

    $provider = new ViaCepProvider;
    $response = $provider->fetch('00000000');

    expect($response)->toBeNull();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://viacep.com.br/ws/00000000/json/';
    });
});

it('fetches address data using custom url passed to constructor', function () {
    $expectedData = [
        'cep' => '01001-000',
        'logradouro' => 'Praça da Sé',
    ];

    Http::fake([
        'https://custom-viacep.test/ws/01001000/json/' => Http::response($expectedData, 200),
    ]);

    $provider = new ViaCepProvider('https://custom-viacep.test/ws/');
    $response = $provider->fetch('01001000');

    expect($response)->toBe($expectedData);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://custom-viacep.test/ws/01001000/json/';
    });
});

it('fetches address data using overridden config viacep_url', function () {
    config()->set('filament-ptbr-form-fields.viacep_url', 'https://custom-config-viacep.test/ws/');

    $expectedData = [
        'cep' => '01001-000',
        'logradouro' => 'Praça da Sé',
    ];

    Http::fake([
        'https://custom-config-viacep.test/ws/01001000/json/' => Http::response($expectedData, 200),
    ]);

    $provider = new ViaCepProvider;
    $response = $provider->fetch('01001000');

    expect($response)->toBe($expectedData);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://custom-config-viacep.test/ws/01001000/json/';
    });
});
