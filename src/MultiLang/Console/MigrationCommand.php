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
use Illuminate\Support\Facades\Config;

class MigrationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'multilang:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a migration following the multilang specifications.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $table = Config::get('multilang.db.texts_table');

        if ('' == $table) {
            $this->error('Couldn\'t create migration.' . PHP_EOL . 'Table name can\'t be empty. Check your configuration.');

            return;
        }

        $this->line('');
        $this->info('Tables: ' . $table);

        $message = 'A migration that creates "' . $table . '" tables will be created in database/migrations directory';

        $this->comment($message);
        $this->line('');

        if ($this->confirm('Proceed with the migration creation? [Yes|no]', true)) {
            $this->line('');

            $this->info('Creating migration...');
            if ($this->createMigration($table)) {
                $this->info('Migration successfully created!');
            } else {
                $this->error(
                    'Couldn\'t create migration.' . PHP_EOL . ' Check the write permissions
                    within the database/migrations directory.'
                );
            }

            $this->line('');
        }
    }

    /**
     * Create the migration.
     *
     * @param  string $table
     * @return bool
     */
    protected function createMigration($table)
    {
        $migrationFile = base_path("database/migrations") . "/" . date('Y_m_d_His') . "_create_multi_lang_texts_table.php";

        if (file_exists($migrationFile)) {
            return false;
        }

        $stubPath = __DIR__ . '/../../stubs/migrations/texts.stub';
        $content = file_get_contents($stubPath);
        if (empty($content)) {
            return false;
        }

        $data = str_replace('{{TEXTS_TABLE}}', $table, $content);

        if (! file_put_contents($migrationFile, $data)) {
            return false;
        }

        return true;
    }
}
