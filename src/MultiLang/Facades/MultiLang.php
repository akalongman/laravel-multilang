<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang\Facades;

use Illuminate\Support\Facades\Facade;

class MultiLang extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'multilang';
    }
}
