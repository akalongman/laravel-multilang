<?php

declare(strict_types=1);

namespace Longman\LaravelMultiLang;

use function array_key_exists;
use function explode;
use function is_array;

class Config
{
    /**
     * Config data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new MultiLang config instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get config parameter
     *
     * @param string $key
     * @param mixed $default
     * @return array|mixed|null
     */
    public function get(?string $key = null, $default = null)
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
