<?php

namespace Tests\Unit;

use Longman\LaravelMultiLang\MultiLang;
use Longman\LaravelMultiLang\Facades\MultiLang as MultiLangFacade;
use GrahamCampbell\TestBenchCore\FacadeTrait;
use Illuminate\Database\Schema\Blueprint;

class MultiLangFacadeTest extends AbstractTestCase
{
    use FacadeTrait;

    public function setUp()
    {
        parent::setUp();

        $schema = $this->app->db->getSchemaBuilder();

        $schema->create('texts', function (Blueprint $table) {
            $table->char('key');
            $table->char('lang', 2);
            $table->text('value')->default('');
            $table->enum('scope', ['admin', 'site', 'global'])->default('global');
            $table->timestamps();
            $table->primary(['key', 'lang', 'scope']);
        });



        $this->inited = true;
    }

    protected function getFacadeAccessor()
    {
        return 'multilang';
    }


    protected function getFacadeClass()
    {
        return MultiLangFacade::class;
    }


    protected function getFacadeRoot()
    {
        return MultiLang::class;
    }
}
