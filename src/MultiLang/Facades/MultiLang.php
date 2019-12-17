<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang\Facades;

class MultiLang extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'multilang';
    }
}
