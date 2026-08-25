<?php

declare(strict_types=1);

use Fixture\Pest\Gate;
use Rasuvaeff\Understudy\Understudy;

use function Rasuvaeff\Understudy\expect as expectCall;
use function Rasuvaeff\Understudy\verify as verifyCall;
use function Rasuvaeff\Understudy\when;

require_once __DIR__ . '/../Gate.php';

it('verifies an expectation declared before the action', function (): void {
    $gate = Understudy::for(Gate::class);
    when(static fn(): bool => $gate->open(7))->returns(true);
    expectCall(static fn(): bool => $gate->open(7));

    $gate->open(7);
});

it('reads a call back after the action', function (): void {
    $gate = Understudy::for(Gate::class);
    when(static fn(): bool => $gate->open(8))->returns(true);

    $gate->open(8);

    verifyCall(static fn(): bool => $gate->open(8));
});

it('leaves Pest its own expect()', function (): void {
    expect(true)->toBeTrue();
});

it('fails when the expectation was never met', function (): void {
    $gate = Understudy::for(Gate::class);

    expectCall(static fn(): bool => $gate->open(9));

    expect(true)->toBeTrue();
});
