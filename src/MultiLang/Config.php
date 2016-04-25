<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\LaravelMultiLang;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Database\DatabaseManager as Database;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Config
{
    /**
     * Config data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new MultiLang instance.
     *
     * @param string                               $environment
     * @param array                                $config
     * @param \Illuminate\Cache\CacheManager       $cache
     * @param \Illuminate\Database\DatabaseManager $db
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get($key = null, $default = null)
    {
        $array = $this->data;

        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }


}
