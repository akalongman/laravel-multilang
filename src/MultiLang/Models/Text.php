<?php

namespace Longman\LaravelMultiLang\Models;

use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'texts';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'value',
    ];
}
