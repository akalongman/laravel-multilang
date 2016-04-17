<?php
/*
 * This file is part of the Laravel MultiLang package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\LaravelMultiLang\Console;

use Illuminate\Console\Command;

class TextsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'multilang:texts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show multilang texts and translations.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $texts = app('multilang')->getTexts();

        if (empty($texts)) {
            $this->info('Application texts is empty');
            return false;
        }

        $headers = ['#', 'Text Key', 'Text Value', 'Language'];

        $rows = [];
        $i = 1;
        foreach ($texts as $lang => $items) {
            foreach ($items as $key => $item) {
                $row = [$i, $key, $item, $lang];
                $rows[] = $row;
                $i++;
            }
        }
        $this->table($headers, $rows);
    }



}
