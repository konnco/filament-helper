<?php

namespace Konnco\FilamentHelper;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Konnco\FilamentHelper\Commands\FilamentHelperCommand;

class FilamentHelperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('filament-helper')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_filament-helper_table')
            ->hasCommand(FilamentHelperCommand::class);
    }

    public function boot()
    {
        Str::macro('numberFormat', function ($str) {
            return number_format($str, 0, ',', '.');
        });

        Str::macro('rupiah', function ($str) {
            $formattedNumber = Str::numberFormat($str);

            return "Rp $formattedNumber";
        });

        TextColumn::macro('rupiah', function (): static {
            return $this->formatStateUsing(fn ($state) => Str::rupiah($state));
        });

        BaseFilter::macro('indicateAs', function ($label, $key = null): static {
            $name = $this->getName();

            return $this->indicateUsing(function ($data) use ($name, $label, $key) {
                if (array_key_exists('value', $data) && count($data) == 1) {
                    if (empty($data['value'])) {
                        return null;
                    }

                    return $label.' '.$data['value'];
                }

                if (! $data[$key ?: $name]) {
                    return null;
                }

                return $label.' '.$data[$key ?: $name];
            });
        });

        return parent::boot();
    }
}
