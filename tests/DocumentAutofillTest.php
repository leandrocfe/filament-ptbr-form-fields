<?php

use Illuminate\Support\Facades\Http;
use Leandrocfe\FilamentPtbrFormFields\Document;
use Leandrocfe\FilamentPtbrFormFields\Providers\CpfCnpjProvider;
use Leandrocfe\FilamentPtbrFormFields\Providers\DocumentProviderInterface;

it('fetches CPF data and requests package 3', function () {
    Http::fake([
        'api.cpfcnpj.com.br/*' => Http::response([
            'nome' => 'Fulano de Tal',
            'endereco' => ['logradouro' => 'Rua das Flores'],
        ], 200),
    ]);

    $provider = new CpfCnpjProvider(token: 'test-token', url: 'https://api.cpfcnpj.com.br/');

    $response = $provider->fetch('123.456.789-09');

    expect($response)->toBeArray()
        ->and($response['nome'])->toBe('Fulano de Tal');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.cpfcnpj.com.br/test-token/3/12345678909');
});

it('fetches CNPJ data and requests package 6', function () {
    Http::fake([
        'api.cpfcnpj.com.br/*' => Http::response([
            'razao' => 'Empresa Exemplo LTDA',
            'fantasia' => 'Exemplo',
        ], 200),
    ]);

    $provider = new CpfCnpjProvider(token: 'test-token', url: 'https://api.cpfcnpj.com.br/');

    $response = $provider->fetch('11.222.333/0001-81');

    expect($response)->toBeArray()
        ->and($response['razao'])->toBe('Empresa Exemplo LTDA');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.cpfcnpj.com.br/test-token/6/11222333000181');
});

it('honors an explicit package', function () {
    Http::fake([
        'api.cpfcnpj.com.br/*' => Http::response(['razao' => 'Empresa Exemplo LTDA'], 200),
    ]);

    $provider = new CpfCnpjProvider(token: 'test-token', package: 5, url: 'https://api.cpfcnpj.com.br/');

    $provider->fetch('11.222.333/0001-81');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.cpfcnpj.com.br/test-token/5/11222333000181');
});

it('returns null when the api reports an error', function () {
    Http::fake([
        'api.cpfcnpj.com.br/*' => Http::response(['erro' => 'Documento não encontrado'], 200),
    ]);

    $provider = new CpfCnpjProvider(token: 'test-token', url: 'https://api.cpfcnpj.com.br/');

    expect($provider->fetch('123.456.789-09'))->toBeNull();
});

it('returns null when the api reports a failing status', function () {
    Http::fake([
        'api.cpfcnpj.com.br/*' => Http::response(['status' => 0], 200),
    ]);

    $provider = new CpfCnpjProvider(token: 'test-token', url: 'https://api.cpfcnpj.com.br/');

    expect($provider->fetch('123.456.789-09'))->toBeNull();
});

it('does not call the api without a token', function () {
    Http::fake();

    $provider = new CpfCnpjProvider(token: null, url: 'https://api.cpfcnpj.com.br/');

    expect($provider->fetch('123.456.789-09'))->toBeNull();

    Http::assertNothingSent();
});

it('exposes the provider through the document contract', function () {
    expect(new CpfCnpjProvider(token: 'test-token'))->toBeInstanceOf(DocumentProviderInterface::class);
});

it('enables the autofill and stays live on blur', function () {
    $field = Document::make('documento')
        ->dynamic()
        ->autofill(CpfCnpjProvider::class, function () {});

    expect($field)->toBeInstanceOf(Document::class)
        ->and($field->isLive())->toBeTrue();
});

it('lets the autofill error message be customized', function () {
    $field = Document::make('documento')
        ->autofillErrorMessage('Documento não localizado')
        ->autofill(CpfCnpjProvider::class, function () {});

    expect($field->getAutofillErrorMessage())->toBe('Documento não localizado');
});

it('rejects a provider that does not implement the contract', function () {
    Document::make('documento')->autofill(stdClass::class, function () {});
})->throws(InvalidArgumentException::class);
