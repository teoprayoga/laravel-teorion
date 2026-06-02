<?php

namespace Teoprayoga\Teorion\Console;

use Illuminate\Console\GeneratorCommand;

class MakeQueryFilterCommand extends GeneratorCommand
{
    protected $name = 'make:query-filter';

    protected $description = 'Create a new Teorion QueryFilter class';

    protected $type = 'QueryFilter';

    protected function getStub(): string
    {
        $publishedStub = base_path('stubs/query-filter.stub');

        return file_exists($publishedStub)
            ? $publishedStub
            : __DIR__ . '/../../stubs/query-filter.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\QueryFilters';
    }
}
