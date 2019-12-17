<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang\Models;

trait Localizable
{
    /**
     * Boot trait
     */
    public static function bootLocalizable()
    {
        static::addGlobalScope(new LocalizableScope());
    }

    /**
     * Get column name
     *
     * @return string
     */
    public function getLocalizableColumn()
    {
        $localizableColumn = static::$localizableColumn ?? 'lang';

        return $localizableColumn;
    }

    /**
     * Get column name with table name
     *
     * @return string
     */
    public function getQualifiedLocalizableColumn()
    {
        return $this->getTable() . '.' . $this->getLocalizableColumn();
    }
}
