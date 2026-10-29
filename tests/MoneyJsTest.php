<?php

use Leandrocfe\FilamentPtbrFormFields\Money;
use Leandrocfe\FilamentPtbrFormFields\PtbrMoney;

beforeEach(function () {
    if (blank(shell_exec('command -v node'))) {
        test()->markTestSkipped('Node.js is required for money.js tests.');
    }
});

function runMoneyJs(string $expression): string
{
    $script = file_get_contents(__DIR__.'/../resources/js/money.js');

    if ($script === false) {
        throw new RuntimeException('Unable to read resources/js/money.js');
    }

    $command = sprintf(
        'node -e %s',
        escapeshellarg($script.PHP_EOL.'process.stdout.write(JSON.stringify('.$expression.'))'),
    );

    $result = shell_exec($command);

    expect($result)->not->toBeNull();

    return json_decode($result, true, flags: JSON_THROW_ON_ERROR);
}

it('masks digit-only input as pt-BR money', function (string $input, string $expected) {
    $result = runMoneyJs('FilamentPtbrFormFields.maskMoney('.json_encode($input).', {locale: \'pt-BR\'})');

    expect($result)->toBe($expected);
})->with([
    ['1', '0,01'],
    ['12', '0,12'],
    ['123', '1,23'],
    ['1234', '12,34'],
    ['100020', '1.000,20'],
    ['1.000,20', '1.000,20'],
    ['1000.20', '1.000,20'],
    ['', '0,00'],
    ['0,00', '0,00'],
    ['100,00', '100,00'],
]);

it('keeps blank values empty when empty option is enabled', function () {
    $result = runMoneyJs('FilamentPtbrFormFields.maskMoney(\'\', {locale: \'pt-BR\', empty: true})');

    expect($result)->toBe('');
});

it('masks usd values with a dot as decimal separator', function () {
    $result = runMoneyJs('FilamentPtbrFormFields.maskMoney(\'100020\', {locale: \'en\'})');

    expect($result)->toBe('1,000.20');
});

it('keeps the currency.masking alias for backward compatibility', function () {
    $result = runMoneyJs('Currency.masking(\'100020\', {locales: \'pt-BR\'})');

    expect($result)->toBe('1.000,20');
});

it('keeps the sign of a negative amount', function () {
    expect(runMoneyJs('FilamentPtbrFormFields.maskMoney(\'-1.000,20\', {locale: \'pt-BR\'})'))->toBe('-1.000,20')
        ->and(runMoneyJs('FilamentPtbrFormFields.maskMoney(\'-0\', {locale: \'pt-BR\'})'))->toBe('0,00');
});

it('supports currencies without fraction digits', function () {
    expect(runMoneyJs('FilamentPtbrFormFields.maskMoney(\'123\', {locale: \'pt-BR\', fractionDigits: 0})'))->toBe('123');
});

it('keeps a lone minus sign so a negative amount can be typed from scratch', function () {
    expect(runMoneyJs('FilamentPtbrFormFields.maskMoney(\'-\', {locale: \'pt-BR\', empty: true})'))->toBe('-')
        ->and(runMoneyJs('FilamentPtbrFormFields.maskMoney(\'-5\', {locale: \'pt-BR\', empty: true})'))->toBe('-0,05');
});

it('formats arbitrarily large digit strings without rounding cents', function (string $input, string $expected) {
    expect(runMoneyJs('FilamentPtbrFormFields.maskMoney('.json_encode($input).', {locale: "pt-BR"})'))->toBe($expected);
})->with([
    ['9007199254740993', '90.071.992.547.409,93'],
    ['9223372036854775807', '92.233.720.368.547.758,07'],
    ['-9223372036854775808', '-92.233.720.368.547.758,08'],
    ['99999999999999999999999999', '999.999.999.999.999.999.999.999,99'],
]);

it('respects readonly and disabled keyboard input and preserves editable operations', function () {
    $setup = <<<'JS'
    global.HTMLInputElement = class {
        constructor(value, readOnly, disabled) {
            this.value = value;
            this.readOnly = readOnly;
            this.disabled = disabled;
            this.selectionStart = 2;
            this.selectionEnd = 2;
            this.events = 0;
        }
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; }
        dispatchEvent() { this.events++; }
    };
    for (const key of ['-', 'Backspace', 'Delete']) {
        for (const [readonly, disabled] of [[true, false], [false, true], [false, false]]) {
            const input = new HTMLInputElement('1.234,56', readonly, disabled);
            if (key === 'Delete') { input.selectionStart = input.selectionEnd = 1; }
            let prevented = false;
            FilamentPtbrFormFields.handleKeydown(input, {key, preventDefault() { prevented = true; }}, {locale: 'pt-BR'});
            const locked = readonly || disabled;
            if (locked && (input.value !== '1.234,56' || input.events !== 0 || prevented)) { throw new Error('Locked input changed'); }
            if (!locked && (input.value === '1.234,56' || input.events !== 1 || !prevented)) { throw new Error('Editable input did not change'); }
        }
    }
    return 'ok';
JS;

    expect(runMoneyJs('(() => {'.$setup.'})()'))->toBe('ok');
});

it('preserves legacy Currency.masking number and string input semantics', function (string $expression, string $expected) {
    expect(runMoneyJs($expression))->toBe($expected);
})->with([
    ['Currency.masking(100)', '100,00'],
    ['Currency.masking("100")', '1,00'],
    ['Currency.masking(1000.2)', '1.000,20'],
    ['Currency.masking("1000.20")', '1.000,20'],
    ['Currency.masking(1.235)', '1,24'],
    ['Currency.masking("1.235")', '12,35'],
    ['Currency.masking(0, {empty: true})', ''],
    ['Currency.masking("0,00", {empty: true})', ''],
    ['Currency.masking(100, {locales: "en-US"})', '100.00'],
    ['Currency.masking("100020", {locales: "en-US"})', '1,000.20'],
]);

it('preserves legacy Currency.masking Intl formatting options and fixed cent input scale', function (string $expression, string $expected) {
    expect(runMoneyJs($expression))->toBe($expected);
})->with([
    ['Currency.masking(100, {options: {minimumFractionDigits: 3, maximumFractionDigits: 3}})', '100,000'],
    ['Currency.masking("100020", {options: {minimumFractionDigits: 3, maximumFractionDigits: 3}})', '1.000,200'],
    ['Currency.masking("100020", {options: {minimumFractionDigits: 0, maximumFractionDigits: 0}})', '1.000'],
    ['Currency.masking(1000.2, {options: {minimumFractionDigits: 0, maximumFractionDigits: 1, useGrouping: false}})', '1000,2'],
    ['Currency.masking(1000.2, {locales: "en-US", options: {style: "currency", currency: "USD", minimumFractionDigits: 2, maximumFractionDigits: 2}})', '$1,000.20'],
]);

it('compiles published and inline init attributes as Alpine expressions', function (bool $published) {
    $expression = Money::make('price')
        ->maskAssetPublished($published)
        ->getExtraAlpineAttributes()['x-init'];

    $script = <<<'JS'
    (() => {
        const expression = EXPRESSION;
        const wrapped = /^if\s*\(/.test(expression.trim()) ? `(() => { ${expression} })()` : expression;
        const AsyncFunction = Object.getPrototypeOf(async function () {}).constructor;
        new AsyncFunction('__self', 'scope', `with (scope) { __self.result = ${wrapped}; __self.finished = true; return __self.result; }`);
        return 'valid';
    })()
JS;

    expect(runMoneyJs(str_replace('EXPRESSION', json_encode($expression, JSON_THROW_ON_ERROR), $script)))->toBe('valid');
})->with([true, false]);

it('preserves complete legacy PtbrMoney Alpine handlers through HTML rendering', function () {
    $field = PtbrMoney::make('legacy_money');
    $attributes = $field->getExtraAlpineAttributes();
    // Match TextInput's unescaped attribute merge, then parse the resulting HTML
    // as the browser does before Alpine receives each expression.
    $html = '<input '.$field->getExtraInputAttributeBag()->merge($attributes, escape: false)->toHtml().'>';
    $document = new DOMDocument;
    $document->loadHTML($html);
    $input = $document->getElementsByTagName('input')->item(0);

    foreach ($attributes as $name => $expression) {
        expect($input->getAttribute($name))->toBe($expression);
    }

    $keyup = json_encode($input->getAttribute('x-on:keyup'), JSON_THROW_ON_ERROR);
    expect(runMoneyJs('(() => {
        const input = {value: "123456"};
        const handler = new Function("$el", "return (" + '.$keyup.' + ")")(input);
        handler();
        return input.value;
    })()'))->toBe('1.234,56');

    $keypress = json_encode($input->getAttribute('x-on:keypress'), JSON_THROW_ON_ERROR);
    expect(runMoneyJs('(() => {
        let prevented = false;
        const event = {keyCode: 65, preventDefault() { prevented = true; }};
        const handler = new Function("event", "return (" + '.$keypress.' + ")")(event);
        handler();
        return String(prevented);
    })()'))->toBe('true');
});
