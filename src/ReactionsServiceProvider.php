<?php

namespace Ritechoice23\Reactions;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ReactionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-reactions')
            ->hasConfigFile('reactions')
            ->hasMigration('2025_11_06_000001_create_reactions_table');
    }
}
