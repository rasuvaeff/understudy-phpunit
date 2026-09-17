<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests\Fixture;

/**
 * The mistake the README warns about: the class overrides
 * `assertPostConditions()` without composing the trait's, so PHP resolves the
 * conflict in the class's favour and the trait's verification is never
 * reached.
 */
final class SwallowingSpy extends SpyUsingTrait
{
    use \Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration {
        assertPostConditions as public understudyAssertPostConditionsForTheTest;
    }

    public bool $ownPostConditionsRan = false;

    #[\Override]
    protected function assertPostConditions(): void
    {
        $this->ownPostConditionsRan = true;
    }
}
