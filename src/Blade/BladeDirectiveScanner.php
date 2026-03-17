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

        // @livewire('name')
        preg_match_all(
            '/@livewire\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            $content,
            $matches,
        );
        foreach ($matches[1] as $match) {
            $references[] = $match;
        }

        // <livewire:name>
        preg_match_all(
            '/<livewire:([a-zA-Z0-9\-_.]+)/',
            $content,
            $matches,
        );
        foreach ($matches[1] as $match) {
            $references[] = str_replace('-', '-', $match);
        }

        return array_values(array_unique($references));
    }

    private function componentTagToViewName(string $tag): string
    {
        // <x-alert> => components.alert
        // <x-forms.input> => components.forms.input
        // Convert kebab-case segments to kebab (they stay as-is in view names)
        $name = str_replace('::', '.', $tag);
        $name = str_replace('-', '-', $name);

        return 'components.' . $name;
    }
}
