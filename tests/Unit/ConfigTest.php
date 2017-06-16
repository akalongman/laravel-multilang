<?php

namespace Tests\Unit;

class ConfigTest extends AbstractTestCase
{

    /**
     * @test
     */
    public function get_config_array()
    {
        $configData = [
            'locales' => [
                'ka' => [
                    'name' => 'Georgian',
                ],
            ],
        ];
        $config = $this->getConfig($configData);

        $this->assertEquals($configData, $config->get());
    }

    /**
     * @test
     */
    public function set_get()
    {
        $config = [
            'locales' => [
                'ka' => [
                    'name' => 'Georgian',
                ],
            ],
        ];
        $config = $this->getConfig($config);

        $this->assertEquals('Georgian', $config->get('locales.ka.name'));
    }

    /**
     * @test
     */
    public function get_default_value()
    {
        $config = [
            'locales' => [
                'ka' => [
                    'name' => 'Georgian',
                ],
            ],
        ];
        $config = $this->getConfig($config);

        $this->assertEquals('Thai', $config->get('locales.th.name', 'Thai'));
    }

    /**
     * @test
     */
    public function table_name()
    {
        $config = [
            'db' => [
                'autosave' => true,
                'connection' => 'mysql',
                'texts_table' => 'texts',
            ],
        ];

        $config = $this->getConfig($config);

        $this->assertEquals('texts', $config->get('db.texts_table'));
    }
}
