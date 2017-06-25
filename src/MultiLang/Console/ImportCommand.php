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

use App;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager as Database;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multilang:import        
        {--path=storage/multilang : The path to multilang folder}
        {--lang= : Comma separated langs to import, default all}
        {--scope= : Comma separated scopes, default all}
        {--force : Force update existing texts in database}
        {--clear : Clear texts from database before import}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import texts in database from yml files.';

    /**
     * The name of texts table.
     *
     * @var string
     */
    protected $table;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $db;

    /**
     * The path to texts files.
     *
     * @var string
     */
    protected $path;

    /**
     * The langs.
     *
     * @var array
     */
    protected $langs;

    /**
     * The available scopes.
     *
     * @var array
     */
    protected $scopes = ['global', 'site', 'admin'];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->table = config('multilang.db.texts_table', 'texts');
        $this->db = $this->getDatabase();

        $lang = $this->option('lang');
        if (! empty($lang)) {
            $this->langs = explode(',', $lang);
        }

        $scopes = $this->scopes;
        $scope = $this->option('scope');
        if (! empty($scope)) {
            $scopes = explode(',', $scope);
            foreach ($scopes as $scope) {
                if (! in_array($scope, $this->scopes)) {
                    throw new InvalidArgumentException('Scope "' . $scope . '" is not found! Available scopes is ' . implode(', ', $this->scopes));
                }
            }
        }

        $path = $this->option('path', 'storage/multilang');
        $this->path = base_path($path);
        if (! is_dir($this->path)) {
            throw new InvalidArgumentException('Folder "' . $this->path . '" is not accessible!');
        }

        $force = $this->option('force');
        $clear = $this->option('clear');
        foreach ($scopes as $scope) {
            $this->import($scope, $force, $clear);
        }
    }

    protected function import($scope = 'global', $force = false, $clear = false)
    {
        $path = $this->path . '/' . $scope . '.yml';
        if (! is_readable($path)) {
            $this->warn('File "' . $path . '" is not readable!');

            return false;
        }
        $data = Yaml::parse(file_get_contents($path));
        if (empty($data)) {
            $this->warn('File "' . $path . '" is empty!');

            return false;
        }

        if ($clear) {
            $this->db
                ->table($this->table)
                ->where('scope', $scope)
                ->delete();
        }

        $created_at = Carbon::now()->toDateTimeString();
        $updated_at = $created_at;
        $inserted = 0;
        $updated = 0;
        foreach ($data as $text) {
            $key = $text['key'];

            foreach ($text['texts'] as $lang => $value) {
                if (! empty($this->langs) && ! in_array($lang, $this->langs)) {
                    continue;
                }

                $row = $this->db
                    ->table($this->table)
                    ->where('scope', $scope)
                    ->where('key', $key)
                    ->where('lang', $lang)
                    ->first();

                if (empty($row)) {
                    // insert row
                    $ins = [];
                    $ins['key'] = $key;
                    $ins['lang'] = $lang;
                    $ins['scope'] = $scope;
                    $ins['value'] = $value;
                    $ins['created_at'] = $created_at;
                    $ins['updated_at'] = $updated_at;
                    $this->db
                        ->table($this->table)
                        ->insert($ins);
                    $inserted++;
                } else {
                    if ($force) {
                        // force update row
                        $upd = [];
                        $upd['key'] = $key;
                        $upd['lang'] = $lang;
                        $upd['scope'] = $scope;
                        $upd['value'] = $value;
                        $upd['updated_at'] = $updated_at;
                        $this->db
                            ->table($this->table)
                            ->where('key', $key)
                            ->where('lang', $lang)
                            ->where('scope', $scope)
                            ->update($upd);
                        $updated++;
                    }
                }
            }
        }

        $this->info('Import texts of "' . $scope . '" is finished. Inserted: ' . $inserted . ', Updated: ' . $updated);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function getDatabase()
    {
        $connection = config('multilang.db.connection', 'default');
        $db = App::make(Database::class);
        if ($connection == 'default') {
            return $db->connection();
        }

        return $db->connection($connection);
    }
}
