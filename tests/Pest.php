<?php

use Leandrocfe\FilamentPtbrFormFields\Money;
use Leandrocfe\FilamentPtbrFormFields\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function invokeMoney(Money $field, string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod($field, $method);

    return $reflection->invoke($field, ...$arguments);
}
