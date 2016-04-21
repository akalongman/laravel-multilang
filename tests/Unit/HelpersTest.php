<?php

namespace Tests\Unit;

/**
 * This is the service provider test class.
 *
 */
class HelpersTest extends AbstractTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->createTable();
    }

    /**
     * @test
     */
    public function t_should_return_valid_translation()
    {
        $multilang = app('multilang');

        $texts = [
            'text1'    => 'value1',
            'text2'    => 'value2',
            'te.x-t/3' => 'value3',
        ];

        $multilang->setLocale('ka', $texts);

        $this->assertEquals('value1', t('text1'));
    }

    /**
     * @test
     */
    public function lang_url_should_return_valid_url()
    {
        $multilang = app('multilang');

        $texts = [
            'text1'    => 'value1',
            'text2'    => 'value2',
            'te.x-t/3' => 'value3',
        ];

        $multilang->setLocale('ka', $texts);

        $this->assertEquals('http://localhost/ka/users/list', lang_url('users/list'));
    }

}
