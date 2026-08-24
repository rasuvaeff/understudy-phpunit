# Examples

| Script | Shows | Needs server? |
|---|---|---|
| `lifecycle.php` | The trait lifecycle driven by hand: fulfilled expectation passes post-conditions, unmet expectation fails them as an assertion, the `#[After]` reset drops the context | No |

Run from the package root:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/lifecycle.php
```

In a real suite none of this plumbing is visible: add
`use UnderstudyPHPUnitIntegration;` to your test class and write plain
understudy code — verification and cleanup happen after every test.
