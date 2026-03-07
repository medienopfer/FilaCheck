<?php

namespace Filacheck\Rules\Concerns;

use Composer\InstalledVersions;
use Illuminate\Support\Str;

trait ResolvesFilamentDocsUrl
{
    protected function filamentDocsUrl(string $path): string
    {
        $version = Str::of(InstalledVersions::getPrettyVersion('filament/support'))
            ->after('v')
            ->before('.')
            ->toString();

        return "https://filamentphp.com/docs/{$version}.x/{$path}";
    }
}
