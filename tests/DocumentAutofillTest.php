<?php

use Illuminate\Http\Client\ConnectionException;
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

it('does not call the api for input that is neither cpf nor cnpj', function () {
    Http::fake();

    $provider = new CpfCnpjProvider(token: 'test-token', url: 'https://api.cpfcnpj.com.br/');

    expect($provider->fetch('123'))->toBeNull();

    Http::assertNothingSent();
});

it('returns null when the http call fails', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $provider = new CpfCnpjProvider(token: 'test-token', url: 'https://api.cpfcnpj.com.br/');

    expect($provider->fetch('123.456.789-09'))->toBeNull();
});

it('reports whether the provider is enabled', function () {
    expect((new CpfCnpjProvider(token: null))->isEnabled())->toBeFalse()
        ->and((new CpfCnpjProvider(token: 'test-token', url: 'https://api.cpfcnpj.com.br/'))->isEnabled())->toBeTrue();
});

it('enables the autofill and stays live on blur', function () {
    config()->set('filament-ptbr-form-fields.cpfcnpj_token', 'test-token');
    config()->set('filament-ptbr-form-fields.cpfcnpj_url', 'https://api.cpfcnpj.com.br/');

    $field = Document::make('documento')
        ->dynamic()
        ->autofill(CpfCnpjProvider::class, function () {});

    expect($field)->toBeInstanceOf(Document::class)
        ->and($field->isLive())->toBeTrue();
});

it('leaves the field untouched when no token is configured', function () {
    config()->set('filament-ptbr-form-fields.cpfcnpj_token', null);

    $field = Document::make('documento')
        ->autofill(CpfCnpjProvider::class, function () {});

    $hooks = (fn () => $this->afterStateUpdated)->call($field);

    expect($field)->toBeInstanceOf(Document::class)
        ->and($hooks)->toBe([]);
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
