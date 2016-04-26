<?php

namespace Tests\Unit;

class ConfigTest extends AbstractTestCase
{

    /**
     * @test
     */
    public function create_with_invalid_data()
    {
        if (version_compare('7.0.0', PHP_VERSION, '>')) {
            $this->markTestSkipped(
                'Skipping test that can run only on a PHP 7.'
            );
        }
        $this->setExpectedException('\ErrorException');

        $config = $this->getConfig();
    }

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
}
