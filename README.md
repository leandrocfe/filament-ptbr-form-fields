# Brazilian pt-BR form fields.

This package provides custom form fields for [Filament](https://filamentphp.com/) that are commonly used in Brazilian web applications, such as CPF/CNPJ validation, phone number formatting, money with currency symbol, and CEP integration with [ViaCep](https://viacep.com.br).

This package uses [LaravelLegends/pt-br-validator](https://github.com/LaravelLegends/pt-br-validator) to validate Brazilian Portuguese fields.

![image demo](https://raw.githubusercontent.com/leandrocfe/filament-ptbr-form-fields/develop/screenshots/v3-example.png)

## Installation

You can install the package via Composer:

```bash
composer require leandrocfe/filament-ptbr-form-fields:"^3.0"
```

### Filament V2 - if you are using Filament v2.x, you can use [this section](https://github.com/leandrocfe/filament-ptbr-form-fields/tree/2.0.0)

## Usage

### CPF / CNPJ

To create a dynamic input that accepts either CPF or CNPJ, use:

```php
use Leandrocfe\FilamentPtbrFormFields\Document;
//CPF or CNPJ
Document::make('cpf_or_cnpj')
    ->dynamic()
```

If you want to create an input that only accepts CPF or only accepts CNPJ, use:

```php
//CPF
Document::make('cpf')
    ->cpf()
```

```php
//CNPJ
Document::make('cnpj')
    ->cnpj()
```

If you want to use a custom mask for the input, use the cpf() or cnpj() method with a string argument representing the desired mask:

```php
Document::make('cpf')
    ->cpf('999999999-99')
```

```php
Document::make('cnpj')
    ->cnpj('99999999/9999-99')
```

### Validation
`Document` uses [LaravelLegends/pt-br-validator](https://github.com/LaravelLegends/pt-br-validator) to validate Brazilian Portuguese fields by default - `cpf_ou_cnpj` | `cpf` | `cnpj`

You can disable validation using the `validation(false)` method:

```php
Document::make('cpf_or_cnpj')
    ->validation(false)
    ->dynamic()
```

```php
Document::make('cpf')
    ->validation(false)
    ->cpf()
```

### Phone number

To create a dynamic input that formats phone numbers with DDD, use:

```php
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;
PhoneNumber::make('phone_number')
```

If you want to use a custom phone number format, use the format() method with a string argument representing the desired format:

```php
PhoneNumber::make('phone_number')
->format('99999-9999')
```

```php
PhoneNumber::make('phone_number')
->format('(+99)(99)99999-9999')
```

### Money

To create a money input with the Brazilian currency symbol as the prefix, use:

```php
use Leandrocfe\FilamentPtbrFormFields\Money;
Money::make('price')
```

If you want to remove the prefix, use the prefix() method with a null argument:

```php
Money::make('price')
->prefix(null)
```

By default, the mask is removed from the input when it is submitted. If you want to keep the mask, use the dehydrateMask() method with a false argument:

```php
Money::make('price')
->dehydrateMask(false)
```

The initial value of the input is '0,00'. If you want to change the initial value, use the initialValue() method with a string argument:

```php
Money::make('price')
->initialValue(null)
```

### Address

To integrate with the ViaCep API for CEP validation and address autofill, use:

```php
use Leandrocfe\FilamentPtbrFormFields\Cep;
use Filament\Forms\Components\TextInput;
Cep::make('postal_code')
    ->viaCep(
        mode: 'suffix', // Determines whether the action should be appended to (suffix) or prepended to (prefix) the cep field, or not included at all (none).
        errorMessage: 'CEP inválido.', // Error message to display if the CEP is invalid.

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
TextInput::make('number'),
TextInput::make('complement'),
TextInput::make('district'),
TextInput::make('city'),
TextInput::make('state'),
```

The mode parameter specifies whether the search action should be appended to or prepended to the CEP field, using the values suffix or prefix. Alternatively, you can use the none value with the `->live(onBlur: true)` method to indicate that the other address fields will be automatically filled only when the CEP field loses focus.

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
