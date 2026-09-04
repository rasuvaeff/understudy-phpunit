<?php

declare(strict_types=1);

namespace Rasuvaeff\Understudy\PhpUnit\Tests;

use Rasuvaeff\Understudy\PhpUnit\UnderstudyPHPUnitIntegration;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

/**
 * The one Usage snippet, wherever it is written.
 *
 * It exists because the snippet was wrong for two releases and nothing
 * noticed: it stubbed `find(7)` with `when()` and claimed the same call with
 * `expect()`, and it armed the expectation below the action. Both are
 * mistakes the engine refuses, and `examples/readme-usage.php` was added to
 * run the corrected form so it could not drift again.
 *
 * That fixed the README and the example. It did not fix the docblock of the
 * shipped trait or `llms.txt` — the two copies nothing executes, which is
 * exactly the condition that let the original live. A consumer reads the
 * docblock in their IDE and an assistant reads `llms.txt`; neither opens
 * `examples/`.
 *
 * So the three are pinned to each other here. The README is the source: it is
 * the one `examples/readme-usage.php` mirrors and actually runs.
 *
 * @internal
 */
#[Test]
#[CoversNothing]
final class DocumentedUsageTest
{
    private const string MARKER = 'public function testChargesForTheCart(): void';

    public function theTraitDocblockShowsTheUsageTheReadmeShows(): void
    {
        $readme = $this->bodyIn($this->read('README.md'));

        Assert::true($readme !== [], 'the README no longer carries the Usage snippet under its marker');
        Assert::same($this->bodyIn($this->traitDocblock()), $readme);
    }

    public function theLlmReferenceShowsTheUsageTheReadmeShows(): void
    {
        Assert::same($this->bodyIn($this->read('llms.txt')), $this->bodyIn($this->read('README.md')));
    }

    /**
     * Both engine rules the snippet used to break, named where the reader of
     * either copy will meet them.
     */
    public function bothCopiesSayWhyTheSnippetHasThatShape(): void
    {
        foreach ([$this->traitDocblock(), $this->read('llms.txt')] as $copy) {
            Assert::string($copy)
                ->contains('rm before the run')
                ->contains('ne registration per call');
        }
    }

    /**
     * The statements of the marked method, normalised: a docblock writes them
     * behind ` * `, and Markdown indents them differently in a fenced block.
     *
     * @return list<string>
     */
    private function bodyIn(string $text): array
    {
        $lines = explode("\n", $text);
        $body = [];
        $inside = false;

        foreach ($lines as $line) {
            $stripped = ltrim(ltrim($line), '*');
            $trimmed = trim($stripped);

            if (!$inside) {
                $inside = str_contains($trimmed, self::MARKER);

                continue;
            }

            if ($trimmed === '}') {
                break;
            }

            if ($trimmed !== '' && $trimmed !== '{') {
                $body[] = $trimmed;
            }
        }

        return $body;
    }

    private function traitDocblock(): string
    {
        $reflection = new \ReflectionClass(UnderstudyPHPUnitIntegration::class);
        $docblock = $reflection->getDocComment();

        Assert::true($docblock !== false, 'the trait lost its docblock');

        return $docblock === false ? '' : $docblock;
    }

    private function read(string $file): string
    {
        $path = \dirname(__DIR__) . '/' . $file;
        $contents = file_get_contents($path);

        Assert::true($contents !== false, $file . ' is unreadable');

        return $contents === false ? '' : $contents;
    }
}
