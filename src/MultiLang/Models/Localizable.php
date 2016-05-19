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


    public static function bootLocalizable()
    {
        static::addGlobalScope(new LocalizableScope);
    }

    public function getLocalizableColumn()
    {
        $localizableColumn = isset(static::$localizableColumn) ? static::$localizableColumn : 'lang';

        return $localizableColumn;
    }


    public function getQualifiedLocalizableColumn()
    {
        return $this->getTable().'.'.$this->getLocalizableColumn();
    }


}
