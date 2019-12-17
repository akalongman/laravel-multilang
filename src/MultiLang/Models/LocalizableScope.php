<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LocalizableScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {

        if (! $this->queryHasLocalizableColumn($builder)) {
            $builder->where($model->getQualifiedLocalizableColumn(), '=', app()->getLocale());
        }
    }

    /**
     * Check if query has "localizable" column
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return bool
     */
    protected function queryHasLocalizableColumn(Builder $builder)
    {
        $wheres = $builder->getQuery()->wheres;
        $column = $this->getLocalizableColumn($builder);
        if (! empty($wheres)) {
            foreach ($wheres as $where) {
                if (isset($where['column']) && $where['column'] === $column) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the "localizable" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @return string
     */
    protected function getLocalizableColumn(Builder $builder)
    {
        if (count($builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedLocalizableColumn();
        } else {
            return $builder->getModel()->getLocalizableColumn();
        }
    }
}
