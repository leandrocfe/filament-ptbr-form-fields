# Brazilian pt-BR form fields.

This package provides custom form fields for [Filament](https://filamentphp.com/) (**>=v2.17.28**) that are commonly used in Brazilian web applications, such as CPF/CNPJ validation, phone number formatting, money with currency symbol, and CEP integration with [ViaCep](https://viacep.com.br).

This package uses [LaravelLegends/pt-br-validator](https://github.com/LaravelLegends/pt-br-validator) to validate Brazilian Portuguese fields.

![image demo](https://raw.githubusercontent.com/leandrocfe/filament-ptbr-form-fields/develop/screenshots/v1-example.png)

## Installation

You can install the package via Composer:

```bash
composer require leandrocfe/filament-ptbr-form-fields
```

## Usage

### CPF / CNPJ

To create a dynamic input that accepts either CPF or CNPJ, use:

```php
use Leandrocfe\FilamentPtbrFormFields\PtbrCpfCnpj;
PtbrCpfCnpj::make('cpf_or_cnpj')
```

You can also add validation to this field by chaining the rule() method:

```php
PtbrCpfCnpj::make('cpf_or_cnpj')
    ->rule('cpf_ou_cnpj')
```

If you want to create an input that only accepts CPF or only accepts CNPJ, use:

```php
//CPF
PtbrCpfCnpj::make('cpf')
    ->cpf()
```

```php
//CNPJ
PtbrCpfCnpj::make('cnpj')
    ->cnpj()
```

You can also add validation to these fields as well:

```php
//CPF with validation
PtbrCpfCnpj::make('cpf')
    ->cpf()
    ->rule('cpf')
```

```php
//CNPJ with validation
PtbrCpfCnpj::make('cnpj')
    ->cnpj()
    ->rule('cnpj')
```

If you want to use a custom mask for the input, use the cpf() or cnpj() method with a string argument representing the desired mask:

```php
PtbrCpfCnpj::make('cpf')
    ->cpf('999999999-99')
```

```php
PtbrCpfCnpj::make('cnpj')
    ->cnpj('99999999/9999-57')
```

### Phone number

To create a dynamic input that formats phone numbers with DDD, use:

```php
use Leandrocfe\FilamentPtbrFormFields\PtbrPhone;
PtbrPhone::make('phone_number')
```

If you want to use a custom phone number format, use the format() method with a string argument representing the desired format:

```php
PtbrPhone::make('phone_number')
->format('99999-9999')
```

```php
PtbrPhone::make('phone_number')
->format('(+99)(99)99999-9999')
```

### Money

To create a money input with the Brazilian currency symbol as the prefix, use:

```php
use Leandrocfe\FilamentPtbrFormFields\PtbrMoney;
PtbrMoney::make('price')
```

If you want to remove the prefix, use the prefix() method with a null argument:

```php
PtbrMoney::make('price')
->prefix(null)
```

By default, the mask is removed from the input when it is submitted. If you want to keep the mask, use the dehydrateMask() method with a false argument:

```php
PtbrMoney::make('price')
->dehydrateMask(false)
```

The initial value of the input is '0,00'. If you want to change the initial value, use the initialValue() method with a string argument:

```php
PtbrMoney::make('price')
->initialValue(null)
```

### Address

To integrate with the ViaCep API for CEP validation and address autofill, use:

```php
use Leandrocfe\FilamentPtbrFormFields\PtbrCep;
use Filament\Forms\Components\TextInput;
PtbrCep::make('postal_code')
    ->viaCep(
        mode: 'suffix', // Determines whether the action should be appended to (suffix) or prepended to (prefix) the cep field, or not included at all (none).
        errorMessage: 'CEP inválido.', // Error message to display if the CEP is invalid.
        eventFocus: 'cep', // Add focus in another element after cep search

        /**
         * Other form fields that can be filled by ViaCep.
         * The key is the name of the Filament input, and the value is the ViaCep attribute that corresponds to it.
         * More information: https://viacep.com.br/
         */
        setFields: [
            'street' => 'logradouro',
            'number' => 'numero',
            'complement' => 'complemento',
            'district' => 'bairro',
            'city' => 'localidade',
            'state' => 'uf'
        ]
    ),

TextInput::make('street'),
TextInput::make('number')
    ->extraAlpineAttributes([
        'x-on:cep.window' => "\$el.focus()", // listen to the focus event and add to the element
    ]),,
TextInput::make('complement'),
TextInput::make('district'),
TextInput::make('city'),
TextInput::make('state'),
```

The mode parameter specifies whether the search action should be appended to or prepended to the CEP field, using the values suffix or prefix. Alternatively, you can use the none value with the ->lazy() method to indicate that the other address fields will be automatically filled only when the CEP field loses focus.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an e-mail to <leandrocfe@gmail.com>.

## Credits

-   [Leandro Costa Ferreira](https://github.com/leandrocfe)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
