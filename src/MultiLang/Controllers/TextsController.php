<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\LaravelMultiLang\Controllers;

use App\Http\Controllers\Controller;
use Longman\LaravelMultiLang\TextsTrait;

class TextsController extends Controller
{
    use TextsTrait;

    protected $view = 'multilang::index';
}
