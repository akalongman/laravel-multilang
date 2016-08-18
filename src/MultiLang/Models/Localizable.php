<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\LaravelMultiLang\Models;

trait Localizable
{

    /**
     * Boot trait
     */
    public static function bootLocalizable()
    {
        static::addGlobalScope(new LocalizableScope);
    }

    /**
     * Get column name
     *
     * @return string
     */
    public function getLocalizableColumn()
    {
        $localizableColumn = isset(static::$localizableColumn) ? static::$localizableColumn : 'lang';

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
