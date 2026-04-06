<?php

declare(strict_types=1);

namespace Renfordt\Prune\Blade;

class BladeDirectiveScanner
{
    /**
     * @return list<string>
     */
    public function scan(string $content): array
    {
        $references = [];

        // @include, @includeIf, @includeFirst, @extends, @component, @each
        // These directives have the view name as the first string argument
        preg_match_all(
            '/@(?:include|includeIf|includeFirst|extends|component|each)\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            $content,
            $matches,
        );
        $references = $matches[1];

        // @includeWhen($condition, 'view'), @includeUnless($condition, 'view')
        // These have the view name as the second argument
        preg_match_all(
            '/@(?:includeWhen|includeUnless)\s*\([^,]+,\s*[\'"]([^\'"]+)[\'"]/',
            $content,
            $matches,
        );
        foreach ($matches[1] as $match) {
            $references[] = $match;
        }

        // <x-component-name>, <x-namespace::component>
        preg_match_all(
            '/<x[-:]([a-zA-Z0-9\-_.]+(?:::[a-zA-Z0-9\-_.]+)?)/',
            $content,
            $matches,
        );
        foreach ($matches[1] as $match) {
            $references[] = $this->componentTagToViewName($match);
        }

        // @livewire('name') — component identifier maps to livewire/<name>.blade.php
        preg_match_all(
            '/@livewire\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            $content,
            $matches,
        );
        foreach ($matches[1] as $match) {
            array_push($references, ...$this->livewireComponentToViewNames($match));
        }

        // <livewire:name> — same convention
        preg_match_all(
            '/<livewire:([a-zA-Z0-9\-_.]+)/',
            $content,
            $matches,
        );
        foreach ($matches[1] as $match) {
            array_push($references, ...$this->livewireComponentToViewNames($match));
        }

        return array_values(array_unique($references));
    }

    /**
     * Converts a component tag into its corresponding view name.
     *
     * @param string $tag The component tag, such as 'x-alert' or 'x-forms.input'.
     * @return string The converted view name, starting with 'components.', where '::' is replaced with '.'.
     */
    private function componentTagToViewName(string $tag): string
    {
        $name = str_replace('::', '.', $tag);

        return 'components.' . $name;
    }

    /**
     * Converts a Livewire component identifier to possible view names.
     *
     * Returns both the standard livewire/ path and the Volt component pattern
     * in the components/ directory (e.g. components/⚡foo/foo.blade.php).
     *
     * @param string $component The Livewire component identifier.
     * @return list<string>
     */
    private function livewireComponentToViewNames(string $component): array
    {
        $segments = explode('.', $component);
        $lastSegment = end($segments);

        return [
            'livewire.' . $component,
            'components.' . $component . '.' . $lastSegment,
        ];
    }
}
