# Examples

| Script | Shows | Needs server? |
|---|---|---|
| `readme-usage.php` | The README's Usage example, executable: one registration that both counts and answers, the failure a subject that never calls produces, and the `when()`-plus-`expect()` pair the engine refuses | No |
| `lifecycle.php` | The trait lifecycle driven by hand: fulfilled expectation passes post-conditions, unmet expectation fails them as an assertion, the `#[After]` reset drops the context | No |

Run from the package root:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/lifecycle.php
```

In a real suite none of this plumbing is visible: add
`use UnderstudyPHPUnitIntegration;` to your test class and write plain
understudy code — verification and cleanup happen after every test.

`_check.php` is the shared assertion helper the scripts include; the leading
underscore is what marks it an include rather than a script of its own.
