<?php

namespace AhmedArafat\AllInOne\Console;
ini_set('memory_limit', '1G');

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use function Laravel\Prompts\progress;

/**
 * Split phpMyAdmin SQL dumps in public/DB into per-database files under public/Result.
 */
class PhpMyAdminDatabaseTablesExtractorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-extract:phpmyadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private string $mainPath = 'DB';
    private string $resultFolder = 'Result';
    private array $allFiles;
    private array $messages = [];
    private string $newFileHeader;
    private string $newDatabaseHeader = '-- Database:';
    private string $commitLine = 'COMMIT;';
    private string $createDatabaseLine = 'CREATE DATABASE IF NOT EXISTS';
    private string $useDatabaseLine = 'USE `';
    private ?string $databaseNamePostfix = '___auto';
    private array $uniqueDatabaseFolders = [];
    private int $numOfDuplicationDatabases = 0;

    /**
     * Collect input files from public/DB and prepare the SQL transaction header.
     */
    public function __construct()
    {
        parent::__construct();
        $this->allFiles = File::allFiles(
            public_path(
                $this->mainPath
            )
        );
        $this->newFileHeader = <<<SQL
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SQL;
    }

    /**
     * Create a directory under the public path if needed and record its creation.
     *
     * @param string $folderName
     */
    private function createFolder($folderName): void
    {
        if (!File::exists(public_path($folderName))) {
            File::makeDirectory(public_path($folderName));
            //$this->info("Created Directory: `$folderName`");
            $this->messages[] = "Created Directory: `$folderName`";
        }
    }

    /**
     * Group SQL lines by database and suffix database names in CREATE DATABASE and USE statements.
     *
     * @param string[] $fileContentArray
     * @param array $allFiles
     */
    private function processSingleFile(&$fileContentArray, &$allFiles): void
    {
        $newFile = $this->newFileHeader;
        $startProcess = false;
        $databaseName = null;
        foreach ($fileContentArray as $line) {
            if (str_contains($line, $this->newDatabaseHeader) && !$startProcess) {
                $databaseName = explode('`', $line)[1];
                $newFile = $this->newFileHeader . $line . PHP_EOL;
                $startProcess = true;
            } else if (str_contains($line, $this->newDatabaseHeader) || str_contains($line, $this->commitLine)) {
                $newFile = $newFile . $this->commitLine;
                $allFiles[$databaseName][] = [
                    'fileName' => $databaseName,
                    'fileSize' => strlen($newFile),
                    'fileContent' => $newFile
                ];
                if (str_contains($line, $this->newDatabaseHeader)) {
                    $databaseName = explode('`', $line)[1];
                    $newFile = $this->newFileHeader . $line . PHP_EOL;
                    $startProcess = true;
                }
            } else if ($startProcess) {
                if (str_contains($line, $this->createDatabaseLine))
                    $newFile .= Str::replace($databaseName, $databaseName . $this->databaseNamePostfix, $line) . PHP_EOL;
                else if (str_contains($line, $this->useDatabaseLine))
                    $newFile .= Str::replace($databaseName, $databaseName . $this->databaseNamePostfix, $line) . PHP_EOL;
                else
                    $newFile .= $line . PHP_EOL;
            }
        }
    }

    /**
     * Read a dump, split it into database sections and write the results.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     */
    private function handleSingleSqlFile($file): void
    {
        $allFiles = [];
        //$this->info('-> Processing File: ' . $file->getFilename());
        $this->messages[] = '--- Processing File: ' . $file->getFilename();
        $fileContent = $file->getContents();
        $fileContentArray = explode("\n", $fileContent);
        $this->processSingleFile($fileContentArray, $allFiles);
        $this->handleFileProcessingResult($allFiles);
    }

    /**
     * Write SQL sections into per-database folders and count repeated database names.
     *
     * @param array $allFiles
     */
    private function handleFileProcessingResult(&$allFiles): void
    {
        foreach ($allFiles as $databaseName => $databases) {
            $this->createFolder($this->resultFolder . '/' . $databaseName);
            foreach ($databases as $key => $database) {
                $newFilePath = public_path($this->resultFolder . '/' . $databaseName . '/' . ($database['fileName'] . '__' . strlen($database['fileSize']) . '__' . $database['fileSize']) . '__' . $key . '.sql');
                file_put_contents($newFilePath, $database['fileContent']);
                //$this->info('  -> Sql File Created: ' . $database['fileName']);
                $this->messages[] = '  |--- Sql File Created: ' . $database['fileName'];
                if (!isset($this->uniqueDatabaseFolders[$databaseName]))
                    $this->uniqueDatabaseFolders[$databaseName] = 0;
                else
                    $this->numOfDuplicationDatabases++;
            }
        }
        //$this->info(null);
        $this->messages[] = null;
    }

    /**
     * Process the input dumps and report output folders and duplicate database sections.
     */
    public function handle(): void
    {
        $this->createFolder($this->resultFolder);
        progress(
            "Processing Folder `$this->mainPath` ...",
            count($this->allFiles),
            function ($num) {
                $this->handleSingleSqlFile($this->allFiles[$num]);
            }
        );
        foreach ($this->messages as $message) {
            $this->info($message);
            $i = 1000000;
            while ($i-- > 0) {}
        }
        $this->info("Done Processing All Files In Folder `$this->mainPath` <3");
        $this->info("Number Of `.sql` Files: " . count($this->allFiles));
        $this->info("Number Of Database Folder(s) Created: " . count($this->uniqueDatabaseFolders));
        $this->info("Number Of Duplicated Database(s): $this->numOfDuplicationDatabases");
    }
}
