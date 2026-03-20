# Prune

A CLI tool that finds orphaned and unused PHP classes in your project using static AST analysis.

Prune parses your PHP files with [nikic/php-parser](https://github.com/nikic/PHP-Parser), collects all class declarations and references, then reports classes that are declared but never referenced anywhere in the scanned codebase. It also supports detecting unused [Blade](https://laravel.com/docs/blade) views in Laravel projects.

## Installation

```bash
composer require --dev renfordt/prune
```

Requires PHP 8.4+.

## Usage

Scan one or more directories:

```bash
php vendor/bin/prune src
php vendor/bin/prune src app lib
```

### Output formats

By default, results are printed as a console table. You can switch to JSON or HTML:

```bash
php vendor/bin/prune src --format=json
php vendor/bin/prune src --format=html
```

JSON and HTML reports are written to `.prune/report.json` or `.prune/report.html` in your project directory. Use `--output` to override the path:

```bash
php vendor/bin/prune src --format=json --output=orphans.json
```

### Blade view analysis

Prune can detect unused Blade views by scanning for `@include`, `@extends`, `@component`, and similar directives, as well as `view()` calls in PHP code.

Blade analysis is enabled by default. To run Blade analysis only (skipping class detection):

```bash
php vendor/bin/prune src --blade
```

### Exit codes

Prune exits with code **1** if any orphans are found, and **0** if the codebase is clean. This makes it suitable for CI pipelines.

## Configuration

Create a `prune.neon` (or `prune.neon.dist`) file in your project root:

```neon
parameters:
    paths:
        - src
        - app
    excludePaths:
        - vendor
        - tests
    extensions:
        - php
    format: console
    blade:
        enabled: true
        viewPaths:
            - resources/views
        excludeViews:
            - errors.404
            - errors.500
```

| Option | Default | Description |
|---|---|---|
| `paths` | `[src]` | Directories to scan for PHP files |
| `excludePaths` | `[vendor]` | Paths to exclude from scanning |
| `extensions` | `[php]` | File extensions to include |
| `format` | `console` | Output format (`console`, `json`, `html`) |
| `blade.enabled` | `true` | Enable Blade view orphan detection |
| `blade.viewPaths` | `[resources/views]` | Directories containing Blade templates |
| `blade.excludeViews` | `[]` | Blade view names to ignore (dot notation) |

You can also point to a custom config file:

```bash
php vendor/bin/prune src --config=custom-prune.neon
```

## How it works

1. **File discovery** -- Symfony Finder collects all PHP files in the configured paths
2. **AST parsing** -- Each file is parsed into an abstract syntax tree using nikic/php-parser
3. **Class map** -- All declared classes, interfaces, traits, and enums are recorded with their fully qualified names
4. **Reference scanning** -- All references to classes are collected: `extends`, `implements`, `new`, type hints, static access, `instanceof`, `catch`, attributes, and trait `use` statements
5. **Orphan detection** -- Any declared class that appears nowhere in the reference set is reported as an orphan
6. **Blade analysis** (optional) -- Blade templates are scanned for view references via directives and PHP `view()` calls; views with no references are reported

## Limitations and risks

Prune performs **static analysis only**. It does not execute your code, so it cannot detect references that only exist at runtime. Be aware of the following before deleting classes based on its output:

- **Dynamic instantiation** -- Classes created via `$class = 'App\\MyClass'; new $class()`, `app()->make(...)`, or similar patterns are invisible to static analysis. This is common in service containers, factories, and plugin systems.
- **String-based references** -- Class names passed as strings (e.g. to configuration arrays, route definitions, or event listeners) are not detected. Laravel service providers, middleware stacks, and config files frequently reference classes this way.
- **Reflection and magic methods** -- Code that uses `ReflectionClass`, `class_exists()`, or `__call`/`__callStatic` to interact with classes dynamically will not be tracked.
- **External consumers** -- If your project is a library, its public API classes may appear unused because the consumers live outside the scanned directories.
- **Framework conventions** -- Some frameworks auto-discover classes by convention (e.g. Laravel commands, event listeners, policies, or Nova resources). These classes may have no explicit references in your codebase.
- **Blade analysis scope** -- Blade view detection only covers `@include`, `@extends`, `@component`, `@each`, `@livewire`, and `view()` calls. Custom directives or JavaScript-driven component rendering are not tracked.
- **Generated code** -- Classes generated at build time or by code generators may not exist at scan time, causing their references to be flagged.
- **Scope of analysis** -- Only files within the configured `paths` are scanned. If a class is referenced in a file outside those paths (e.g. in a test suite or a script not under `src/`), it will still be reported as orphaned.

**Recommendation:** Treat Prune's output as a starting point for investigation, not as a delete list. Review each reported class before removing it.

## License

MIT
