<?php

namespace Konnco\FilamentHelper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Konnco\FilamentHelper\FilamentHelper
 */
class FilamentHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Konnco\FilamentHelper\FilamentHelper::class;
    }
}
