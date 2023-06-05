<?php

namespace Konnco\FilamentHelper\Commands;

use Illuminate\Console\Command;

class FilamentHelperCommand extends Command
{
    public $signature = 'filament-helper';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
