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
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager as Database;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

class ExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multilang:export        
        {--path=storage/multilang : The path to multilang folder}
        {--lang= : Comma separated langs to export, default all}
        {--scope= : Comma separated scopes, default all}
        {--force : Force update existing texts in files}
        {--clear : Clear texts from files before export}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export texts from database to yml files.';

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
            if (! mkdir($this->path, 0777, true)) {
                throw new InvalidArgumentException('unable to create the folder "' . $this->path . '"!');
            }
        }
        if (! is_writable($this->path)) {
            throw new InvalidArgumentException('Folder "' . $this->path . '" is not writable!');
        }

        $force = $this->option('force');
        $clear = $this->option('clear');
        foreach ($scopes as $scope) {
            $this->export($scope, $force, $clear);
        }

        return;

        /*$texts = Text::where('scope', 'site')->where('lang', 'en')->get()->toArray();


        $newTexts = [];
        foreach($texts as $text) {
            $arr = [];
            $arr['key'] = $text['key'];
            $arr['texts']['en'] = $text['value'];
            $arr['texts']['ir'] = $text['value'];

            $newTexts[] = $arr;
        }

        $yaml = Yaml::dump($newTexts, 3, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);*/

        $path = storage_path('texts/site.yml');

        //dump(file_put_contents($path, $yaml));
        //die;

        $value = Yaml::parse(file_get_contents($path));
        dump($value);
        die;

        //$this->info('Database backup restored successfully');
    }

    protected function export($scope = 'global', $force = false, $clear = false)
    {
        $dbTexts = $this->getTextsFromDb($scope);

        $fileTexts = ! $clear ? $this->getTextsFromFile($scope) : [];

        $textsToWrite = $force ? array_replace_recursive($fileTexts, $dbTexts) : array_replace_recursive($dbTexts, $fileTexts);

        // Reset keys
        $textsToWrite = array_values($textsToWrite);

        $yaml = Yaml::dump($textsToWrite, 3, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        $path = $this->path . '/' . $scope . '.yml';
        $written = file_put_contents($path, $yaml);
        if (! $written) {
            $this->error('Export texts of "' . $scope . '" is failed!');
        }

        $this->info('Export texts of "' . $scope . '" is finished in "' . $path . '"');
    }

    /**
     * Get a texts from file.
     *
     * @return array
     */
    protected function getTextsFromFile($scope)
    {
        $fileTexts = [];
        $path = $this->path . '/' . $scope . '.yml';
        if (is_readable($path)) {
            $fileTexts = Yaml::parse(file_get_contents($path));
        }

        $formattedFileTexts = [];
        foreach ($fileTexts as $text) {
            $formattedFileTexts[$text['key']] = $text;
        }

        return $formattedFileTexts;
    }

    /**
     * Get a texts from database.
     *
     * @return array
     */
    protected function getTextsFromDb($scope)
    {
        $dbTexts = $this->db
            ->table($this->table)
            ->where('scope', $scope)
            ->get();

        $formattedDbTexts = [];
        foreach ($dbTexts as $text) {
            $key = $text->key;
            $lang = $text->lang;
            if (! isset($formattedDbTexts[$key])) {
                $formattedDbTexts[$key] = ['key' => $key];
            }
            $formattedDbTexts[$key]['texts'][$lang] = $text->value;
        }

        return $formattedDbTexts;
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
