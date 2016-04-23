<?php

//namespace App\Http\Controllers;
namespace Longman\LaravelMultiLang\Controllers;

use App\Http\Controllers\Controller;
use Longman\LaravelMultiLang\TextsTrait;

class TextsController extends Controller
{
    use TextsTrait;
    protected $view = 'multilang::index';
}
