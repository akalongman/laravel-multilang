<?php
declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Longman\LaravelMultiLang\Facades\MultiLang as MultiLangFacade;
use Longman\LaravelMultiLang\MultiLang;

class MultiLangFacadeTest extends AbstractTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var \Illuminate\Database\Schema\MySqlBuilder $schema */
        $schema = $this->app['db']->getSchemaBuilder();

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
