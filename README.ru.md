# rasuvaeff/understudy-phpunit

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/understudy-phpunit/v)](https://packagist.org/packages/rasuvaeff/understudy-phpunit)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/understudy-phpunit/downloads)](https://packagist.org/packages/rasuvaeff/understudy-phpunit)
[![Build](https://github.com/rasuvaeff/understudy-phpunit/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/understudy-phpunit/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/understudy-phpunit/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/understudy-phpunit/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/understudy-phpunit/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/understudy-phpunit/php)](https://packagist.org/packages/rasuvaeff/understudy-phpunit)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[English version](README.md)

PHPUnit-адаптер для [rasuvaeff/understudy](https://github.com/rasuvaeff/understudy) —
библиотеки test double, где настроенный вызов — это настоящий вызов:
`when(fn () => $repo->find(123))->returns($book)`.

Трейт завершает каждый тест служебной работой understudy за вас:

- **verify после успеха** — после тела, дошедшего до `assertPostConditions()`,
  проверяются все `expect()`. Ожидание, которое код не выполнил, валит тест
  как провал ассерта;
- **исходная ошибка главнее** — после упавшего тела ничего не верифицируется,
  поэтому адаптер никогда не маскирует настоящую ошибку;
- **reset всегда** — хук `#[After]` безусловно сбрасывает контекст. Один тест
  не может протечь дублем в следующий;
- **ранний guard** — хук `#[Before]` отказывается стартовать поверх контекста,
  оставленного предыдущим тестом, — именно так выглядит сломанная интеграция.

> Используете AI-ассистента? [llms.txt](llms.txt) — компактный API-справочник,
> который он может загрузить вместо догадок.

## Требования

- PHP 8.3 – 8.5
- `phpunit/phpunit` (`^11.5 || ^12.0 || ^13.0`)
- `rasuvaeff/understudy` (`^0.1`)

Pest тоже работает: он стоит на PHPUnit, поэтому тот же трейт подключается
через `uses()`. Проверено на Pest 4 — см. секцию Pest ниже.

## Установка

```bash
composer require --dev rasuvaeff/understudy-phpunit
```

## Использование

```php
<?php

use function Rasuvaeff\Understudy\expect;
use function Rasuvaeff\Understudy\when;

use PHPUnit\Framework\TestCase;
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Rasuvaeff\Understudy\Understudy;

final class CheckoutTest extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    public function testChargesForTheCart(): void
    {
        $books = Understudy::for(BookRepositoryInterface::class);
        when(fn () => $books->find(7))->returns($expected = new Book(7));

        $receipt = (new Checkout($books))->charge([7]);

        self::assertSame($expected->price, $receipt->total);
        expect(fn () => $books->find(7));   // ровно один раз — проверено за вас
    }
}
```

Если сервис так и не вызвал `find(7)`, тест падает после тела — с отчётом о
невыполненном ожидании, а не с молчаливым «зелёным».

### Strict stubs

Базовый класс может включить строгость для всего проекта:

```php
abstract class ProjectTestCase extends TestCase
{
    use UnderstudyPHPUnitIntegration;

    protected function understudyStrictStubs(): bool
    {
        return true;
    }
}
```

Настроенный, но ни разу не вызванный стаб тогда валит тест — прочтение
Mockito: «зачем настраивали, если он не нужен?». Точечная строгость на
конкретный дубль доступна через `Understudy::strict($double)` независимо от
этой настройки.

### Собственный `assertPostConditions()`

PHP разрешает конфликт имени метода между классом и трейтом молча в пользу
класса — верификация трейта прекратится без всякой ошибки. Компонуйте явно:

```php
use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration {
    UnderstudyPHPUnitIntegration::assertPostConditions as understudyAssertPostConditions;
}

protected function assertPostConditions(): void
{
    // ваши post-conditions ...
    $this->understudyAssertPostConditions();
}
```

Трейт вызывает `parent::assertPostConditions()` до верификации, поэтому ваши
собственные post-conditions выполняются всегда, а их провал сообщается раньше
неисполненного ожидания — выигрывает проверка, которая ближе к телу теста. В
явной композиции сохраняйте тот же порядок.

### Pest

Pest уже занимает глобальную функцию `expect()`, поэтому глагол настройки
understudy импортируется под другим именем:

```php
use function Rasuvaeff\Understudy\expect as expectCall;
use function Rasuvaeff\Understudy\verify as verifyCall;

uses(UnderstudyPHPUnitIntegration::class)->in(__DIR__);

it('charges for the cart', function () {
    $books = Understudy::for(BookRepositoryInterface::class);
    when(fn () => $books->find(7))->returns(new Book(7));
    expectCall(fn () => $books->find(7));      // объявлено до действия

    (new Checkout($books))->charge([7]);
});

it('reads the call back afterwards', function () {
    $books = Understudy::for(BookRepositoryInterface::class);
    when(fn () => $books->find(7))->returns(new Book(7));

    (new Checkout($books))->charge([7]);

    verifyCall(fn () => $books->find(7));      // после действия
});
```

`expect()` — заявление, сделанное **до** запуска кода под тестом: оно считает
вызовы, пришедшие после него, а не те, что уже случились. Прочитать вызов
задним числом — это `verify()`. Собственный `expect()` Pest продолжает
работать, а бесколлизионная статическая форма
`Understudy::when()/expect()/verify()` работает везде.

Обе формы исполняются проектом `tests/Integration/Fixtures/Pest`;
`make test-pest` ставит его зависимости и запускает.

## API

| Член | Назначение |
|---|---|
| `UnderstudyPHPUnitIntegration` | Трейт: verify-after-success, сброс через `#[After]`, guard `#[Before]`, опциональные строгие стабы на весь проект |

Всё остальное — `for()`, `when()`, `expect()`, `verify()`, матчеры, forwarding,
`wire()` — относится к [rasuvaeff/understudy](https://github.com/rasuvaeff/understudy)
и документировано там. Этот пакет не добавляет собственных операций.

## Примеры

См. [`examples/`](examples/README.md).

## Разработка

На хосте нет PHP/Composer — всё запускается через Docker:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer test:integration
```

Или через Make: `make build`, `make cs-fix`, `make psalm`, `make test`.

Интеграционный suite запускает настоящие процессы PHPUnit над фикстурами из
`tests/Integration/Fixtures/`; внешние сервисы не нужны.

## Лицензия

[BSD-3-Clause](LICENSE.md)
