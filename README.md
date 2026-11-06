# Laravel All-In-One Toolkit

A **curated Laravel toolkit** that centralizes common API patterns, validation rules, Eloquent querying utilities, console commands, and developer productivity helpers — designed to reduce boilerplate and enforce consistency across projects.

Built for real-world Laravel applications, not demos.

---

## Badges

![PHP](https://img.shields.io/badge/php-%3E%3D8.1-blue)
![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-red)
[![MIT License](https://img.shields.io/badge/license-MIT-blue)](LICENSE) <br>
![Packagist Version](https://img.shields.io/packagist/v/ahmed-arafat/laravel-allinone-toolkit) <br>
![Packagist Downloads](https://img.shields.io/packagist/dt/ahmed-arafat/laravel-allinone-toolkit)  <br>
![Maintained](https://img.shields.io/badge/maintained-yes-success)  <br>
![Stable](https://img.shields.io/badge/stability-stable-success)  <br>

---

## Introduction

Laravel All-In-One Toolkit provides a structured set of reusable **traits**, **helpers**, **middleware**, **exceptions**, and **Artisan commands** that solve recurring problems in Laravel projects.

Instead of reimplementing the same logic across applications, this toolkit offers opinionated, Laravel-native abstractions for:

- API response consistency
- Validation rule composition
- Query filtering, searching, and sorting
- Pagination enhancements
- Date handling and formatting
- File and Excel data processing
- Database seeding and automation

---

## Design Philosophy

- Prefer explicit and readable helpers over hidden magic
- Reduce repetitive boilerplate across projects
- Follow Laravel conventions instead of reinventing them
- Keep utilities framework-aware, not framework-agnostic
- Provide small, composable building blocks

---

## Installation

Install the package via Composer:

```bash
composer require ahmed-arafat/laravel-allinone-toolkit
```

The package is auto-discovered by Laravel. No manual registration is required.

---

# API Reference (Purpose & Method Signatures)

Each entry describes a package class or trait and lists its declared methods. Visibility in each signature indicates which methods callers can use directly.

---

## Console Commands

### `DatabaseInitialSeedersCommand`

**Purpose:**
Run the seeders listed in the package configuration with the db:setup command.

```php
class DatabaseInitialSeedersCommand extends Command
{
    /**
     * Load the seeder class names from all-in-one.database_seeders.
     */
    public function __construct();

    /**
     * Run the configured seeders in one database transaction and display their progress.
     */
    private function executeAllSeeders(): void;

    /**
     * Run the configured seeders and report completion.
     */
    public function handle(): void;
}
```

---

### `GitCommand`

**Purpose:**
Stage local changes, commit them with a Cairo timestamp and push to the requested remote and branch.

```php
class GitCommand extends Command
{
    /**
     * Display Git status, stage changes, commit with the supplied message and push the selected branch.
     */
    public function handle(): int;
}
```

---

### `PhpMyAdminDatabaseTablesExtractorCommand`

**Purpose:**
Split phpMyAdmin SQL dumps in public/DB into per-database files under public/Result.

```php
class PhpMyAdminDatabaseTablesExtractorCommand extends Command
{
    /**
     * Collect input files from public/DB and prepare the SQL transaction header.
     */
    public function __construct();

    /**
     * Create a directory under the public path if needed and record its creation.
     */
    private function createFolder($folderName): void;

    /**
     * Group SQL lines by database and suffix database names in CREATE DATABASE and USE statements.
     */
    private function processSingleFile(&$fileContentArray, &$allFiles): void;

    /**
     * Read a dump, split it into database sections and write the results.
     */
    private function handleSingleSqlFile($file): void;

    /**
     * Write SQL sections into per-database folders and count repeated database names.
     */
    private function handleFileProcessingResult(&$allFiles): void;

    /**
     * Process the input dumps and report output folders and duplicate database sections.
     */
    public function handle(): void;
}
```

---

## Exceptions

### `ExceptionWithJsonApiResponser`

**Purpose:**
Provide JSON response helpers to exception subclasses through JsonApiResponser.

```php
class ExceptionWithJsonApiResponser extends Exception
{
    use JsonApiResponser;
}
```

---

### `ValidationErrorsAsArrayException`

**Purpose:**
Identify exceptions rendered as an array of validation messages by ExceptionHelper.

```php
class ValidationErrorsAsArrayException extends Exception
{
    /**
     * Pass the validation message, code and previous exception to the base exception.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null);
}
```

---

## Traits (Core Helpers)

### `ApiResponser`

**Purpose:**
Build consistent array payloads for API responses.

```php
trait ApiResponser
{
    /**
     * Build a successful API response payload.
     */
    protected function apiSuccess(
        mixed $data = null,
        ?string $message = null,
        array $meta = []
    ): array;

    /**
     * Build a failed API response payload.
     */
    protected function apiError(
        string $message,
        array $errors = [],
        array $meta = []
    ): array;

    /**
     * Build a paginated API response payload.
     */
    protected function apiPaginated(
        LengthAwarePaginator $paginator,
        ?string $message = null
    ): array;

    /**
     * Build a response that contains only a message.
     */
    protected function apiMessage(string $message): array;
}
```

---

### `CustomRule`

**Purpose:**
Build reusable password, database and text validation rules.

```php
trait CustomRule
{
    /**
     * Build a strong password validation rule.
     */
    public function strongPassword(
        int $min = 8,
        bool $hasMixed = true,
        bool $hasNumbers = true,
        bool $hasSymbols = true,
        bool $uncompromised = true
    ): Password;

    /**
     * Build an "exists" validation rule with optional constraints.
     */
    public function existsRule(
        string $table,
        string $column = 'id',
        ?Closure $whereClosure = null
    ): Exists;

    /**
     * Build a "unique" validation rule with optional constraints.
     */
    public function uniqueRule(
        string $table,
        string $column,
        ?Closure $whereClosure = null,
        mixed $ignoreId = null
    ): Unique;

    /**
     * Build an "exists" rule that validates only active records.
     */
    public function existsActiveRule(
        string $table,
        string $column = 'id',
        string $activeColumn = 'is_active'
    ): Exists;

    /**
     * Build a scoped "unique" validation rule.
     */
    public function uniqueScopedRule(
        string $table,
        string $column,
        string $scopeColumn,
        mixed $scopeValue,
        mixed $ignoreVal = null,
        mixed $ignoreCol = null,
    ): Unique;

    /**
     * Return a validation regex accepting ASCII letters, spaces and hyphens.
     */
    public function EnglishTextOnly(): string;

    /**
     * Return a validation regex accepting Arabic-script characters and spaces.
     */
    public function ArabicTextOnly(): string;
}
```

---

### `Traits\DateHelper`

**Purpose:**
Parse, compare and format dates with Carbon.

```php
trait DateHelper
{
    /**
     * Parse a date value into a Carbon instance.
     */
    protected function parseDate(DateTimeInterface|string $date, string $timeZone = null): Carbon;

    /**
     * Get the exact difference between two dates in years, months, and days.
     */
    public function diffInYearsMonthsDays(DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): array;

    /**
     * Determine if a date falls between two other dates (inclusive).
     */
    public function isBetweenDates(DateTimeInterface|string $date, DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): bool;

    /**
     * Check if a given date is in the past.
     */
    public function isPastDate(DateTimeInterface|string $date): bool;

    /**
     * Check if a given date is in the future.
     */
    public function isFutureDate(DateTimeInterface|string $date): bool;

    /**
     * Convert a date to ISO 8601 format.
     */
    public function toIsoDate(DateTimeInterface|string $date): string;

    /**
     * Format a date safely using a given format and optional timezone.
     */
    public function formatDate(DateTimeInterface|string $date, string $format = 'Y-m-d', string $timeZone = null): ?string;

    /**
     * Calculate age in years from a given birthdate.
     */
    public function calculateAge(DateTimeInterface|string $birthDate): int;

    /**
     * Add business days (weekdays only) to a given date.
     */
    public function addBusinessDays(DateTimeInterface|string $date, int $days): Carbon;
}
```

---

### `EnumHelper`

**Purpose:**
Look up enum cases and convert backed values into display names and model-style arrays.

```php
trait EnumHelper
{
    /**
     * Get all enum values.
     */
    public static function values(): array;

    /**
     * Get all enum case names.
     */
    public static function names(): array;

    /**
     * Get an enum case by its name.
     */
    public static function fromName(string $name): ?UnitEnum;

    /**
     * Get enum value by enum name.
     */
    public static function valueFromName(string $name): int|string|null;

    /**
     * Get enum name by its backed value.
     */
    public static function nameFromValue(int|string $value): ?string;

    /**
     * Transform enum cases into an array suitable for APIs, dropdowns, selects, or frontend consumption.
     */
    public static function toModelArray(): array;

    /**
     * Get a human-readable name from a backed enum value.
     */
    public static function humanNameFromValue(int|string $value): ?string;

    /**
     * Convert an enum case name to a human-readable string.
     */
    protected static function humanize(string $name): string;
}
```

---

### `ExcelFormatter`

**Purpose:**
Read uploaded worksheets as rows or as values keyed by Excel cell address.

```php
trait ExcelFormatter
{
    /**
     * Extract Excel data from an uploaded file in the request.
     */
    public function excelFileExtractor(
        string $fileKey,
        bool $likeExcelCells = true,
        int $sheetIndex = 0,
        bool $skipEmptyCells = true
    ): array;

    /**
     * Extract Excel data from an UploadedFile instance.
     */
    protected function extractFromUploadedFile(
        UploadedFile $file,
        bool $likeExcelCells = true,
        int $sheetIndex = 0,
        bool $skipEmptyCells = true
    ): array;

    /**
     * Convert rows to Excel-like cell addresses (A1, B2, AA10, ...).
     */
    protected function formatExcelRows(array $rows, bool $skipEmptyCells = true): array;

    /**
     * Convert row & column numbers to Excel cell address.
     */
    private function getExcelCellAddress(int $row, int $col): string;

    /**
     * Ensure maatwebsite/excel is installed.
     */
    private function ensureExcelPackageIsInstalled(): void;
}
```

---

### `FileHelper`

**Purpose:**
Decode stored JSON files and collect metadata about uploaded files.

```php
trait FileHelper
{
    /**
     * Read and decode a JSON file from a given filesystem disk.
     */
    private function getJsonFileContent(string $path, string $disk = 'local'): array;

    /**
     * Extract metadata from an uploaded file in the request.
     */
    public static function uploadedFileMetaData(
        Request $request,
        string $inputName = 'file'
    ): ?array;
}
```

---

### `GenericHelperTrait`

**Purpose:**
Format byte counts for display.

```php
trait GenericHelperTrait
{
    /**
     * Divide the byte count by 1,048,576 and format it with two decimal places and an MB suffix.
     */
    public function convertBytes($bytes): string;
}
```

---

### `JsonApiResponser`

**Purpose:**
Wrap API payloads in Laravel JSON responses with the requested HTTP status.

```php
trait JsonApiResponser
{
    use ApiResponser;

    /**
     * Return a successful JSON response.
     */
    public function jsonSuccess(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse;

    /**
     * Return a failed JSON response.
     */
    public function jsonError(
        string $message,
        int $status = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse;

    /**
     * Return a standardized paginated JSON response.
     */
    protected function jsonPaginated(
        LengthAwarePaginator $paginator,
        ?string $message = null,
        int $status = 200
    ): JsonResponse;

    /**
     * Return a JSON response with only a message.
     */
    public function jsonMessage(
        string $message,
        int $status = 200
    ): JsonResponse;
}
```

---

### `MigrationHelperTrait`

**Purpose:**
Define translation tables and file metadata columns in migrations.

```php
trait MigrationHelperTrait
{
    /**
     * Create a translations table with a cascading foreign key and one row per parent and locale.
     */
    public function createTranslatableRelation($tableName, $cols): void;

    /**
     * Add prefixed stored and original filename columns with optional size and extension columns.
     */
    public static function fileColumn(Blueprint &$table, string $prefix, bool $hasSize = false, bool $hasExtension = false, $isNullable = false): void;

    /**
     * Add standard file name, original name, size and extension columns.
     */
    public function makeFilesColumns(Blueprint &$table): void;
}
```

---

### `PaginatorHelper`

**Purpose:**
Add a continuous row number to each item across pages.

```php
trait PaginatorHelper
{
    /**
     * Append sequential row numbers to paginated items.
     */
    public function addRowNumbers(
        LengthAwarePaginator $paginator,
        ?Request $request = null,
        string $perPageKey = 'per_page',
        string $pageKey = 'page',
        string $attribute = 'num',
        int $defaultPerPage = 10
    ): void;
}
```

---

### `QueryParameter`

**Purpose:**
Look up a record by a numeric query parameter or validate a comma-separated list of IDs.

```php
trait QueryParameter
{
    /**
     * Validate and resolve a single query parameter into a model instance.
     */
    public function resolveQueryModel(
        Request $request,
        string $keyName,
        string $model,
        string $column = 'id'
    ): ?array;

    /**
     * Return the distinct integer IDs after verifying that each exists in the selected column.
     */
    public function resolveQueryModels(
        Request $request,
        string $keyName,
        string $model,
        string $column = 'id'
    ): ?array;
}
```

---

### `SeederHelperTrait`

**Purpose:**
Seed English and Arabic translations together with optional shared model attributes.

```php
trait SeederHelperTrait
{
    /**
     * Update or create records by sequential ID from aligned English and Arabic seed rows.
     */
    public function seederInsertionForTranslatableModels(array &$data, $model, array $cols = [], string $add_cols = null): void;
}
```

---

## Eloquent Traits

For example, expose a filter named `type` for the `category` column and allow keyword searches on product names.

```php
use AhmedArafat\AllInOne\Traits\Eloquent\FilterableTrait;
use AhmedArafat\AllInOne\Traits\Eloquent\SearchableTrait;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use FilterableTrait, SearchableTrait;

    protected $filterableColumns = ['products' => ['columns' => ['type' => 'category']]];
    protected $searchableColumns = ['products' => ['columns' => ['name']]];
}
```

For a request to `/products?filter_col=type&filter_val=fruit&search=apple`, the following query returns fruit with a matching name.

```php
$products = Product::query()->filter()->search()->get();
```

### `FilterableTrait`

**Purpose:**
Filter model and related-table columns using the configured query parameter mappings.

```php
trait FilterableTrait
{
    /**
     * Add an equality condition on a table-qualified column.
     */
    private function filterBuilder(Builder &$q, $col, string $table, string $search): void;

    /**
     * Apply the filter value directly to a column on the model's table.
     */
    public function filterInSameRelation(Builder &$q, &$column, $search, $relation): void;

    /**
     * Require a related row whose mapped column equals the filter value.
     */
    public function filterInAnotherRelation(Builder &$q, &$column, $search, $relation, $methodName): void;

    /**
     * Split a comma-separated filter parameter and reject empty entries.
     */
    private function checkFormat($url, $keyName);

    /**
     * Apply paired column and value filters from the request using the model's filterableColumns map.
     */
    public function scopeFilter(Builder $q, string $filterColKey = 'filter_col', string $filterValKey = 'filter_val'): Builder;
}
```

---

### `SearchableTrait`

**Purpose:**
Search configured columns on the model and its relationships using the search query parameter.

```php
trait SearchableTrait
{
    /**
     * Add LIKE predicates for each word and column and order by match count.
     */
    private function searchBuilder(Builder &$query, $columnsArray, string $table, string $search): void;

    /**
     * Build a SQL expression that counts matching word and column pairs.
     */
    private function matchCountExpressionBuilder(array &$columnsArray, string &$table, array &$words, array $binding): string;

    /**
     * Group keyword predicates for columns on the current model.
     */
    public function searchInSameRelation(Builder &$q, array &$columns, $search, $relation, &$countIterations): void;

    /**
     * Group keyword predicates within a relationship existence query.
     */
    public function searchInAnotherRelation(Builder &$q, array &$columns, $search, $relation, $methodName, &$countIterations): void;

    /**
     * Apply the request's search terms to the model's configured searchableColumns.
     */
    public function scopeSearch(Builder $q): Builder;
}
```

---

### `SortableTrait`

**Purpose:**
Order Eloquent results by configured local or related columns.

```php
trait SortableTrait
{
    /**
     * Build a CONCAT expression using qualified column names and the configured separator.
     */
    public function handelConcatRawOrderBy(array &$data, string $relationName): string;

    /**
     * Return the configured CONCAT expression or an empty string for other operations.
     */
    public function handleRawOrderBy(array &$data, string $relationName): string;

    /**
     * Look up the requested sort key and return its column and relationship metadata.
     */
    public function validateColumns($target): array;

    /**
     * Order the query by a configured column in ascending or descending order.
     */
    public function scopeSortByColumn(Builder $q, $target = null, $dir = null): Builder;

    /**
     * Join the related table and select a sort alias, using MAX for a has-many relation.
     */
    public function addOrderByColumnFromRelation(Builder $query, $relation_name, $operator = '=', $type = 'left', $where = false): Builder;

    /**
     * Qualify the model's column names with the supplied table name.
     */
    protected function getTableColumns($table): array;
}
```

---

## Helper Classes

### `CustomExcelSheetHandler`

**Purpose:**
Validate and extract worksheet data indexed by Excel cell address, collecting errors for the calling handler.

```php
class CustomExcelSheetHandler
{
    use ExcelFormatter;

    /**
     * Initialize the cell-address map and the list of validation errors.
     */
    public function __construct(
        protected array $excelSheet,
        protected array $errors = []
    );

    /**
     * Convert an Excel serial date to a DateTime instance.
     */
    public static function convertExcelDate($excelDate);

    /**
     * Record missing titles and titles whose values differ from the configured text.
     */
    protected function validateTitleCells();

    /**
     * Check consecutive cells for emptiness, or require values when reverse is enabled.
     */
    protected function CellsShouldBeEmptyRecursive($col, $row, $reverse = false);

    /**
     * Check each configured column starting at its specified row.
     */
    protected function validateCellsShouldBeEmpty($reverse = false);

    /**
     * Check nonempty values in consecutive cells against the allowed set.
     */
    protected function hasEnumValuesRecursive($array, $col, $row);

    /**
     * Record nonnumeric values while walking the column from the given row.
     */
    protected function mustBeNumeric($col, int $row);

    /**
     * Check whether a value is numeric and meets the minimum Excel serial date of 36526.
     */
    private function isDate($date);

    /**
     * Validate consecutive populated cells as Excel serial dates, stopping at the first empty cell.
     */
    protected function mustBeDate($col, int $row);

    /**
     * Record an error for each required cell that compares equal to null.
     */
    protected function validateRequiredCells();

    /**
     * Report partially filled rows until an empty row, missing cell or exclusive row limit.
     */
    protected function validateWholeCellExists(array $cols, int $row, $msg, $i = 1, $limitRow = null);

    /**
     * Append rows to the result using the supplied keys, stopping at missing cells or the exclusive limit.
     */
    protected function extractWholeRowsAsArray(array $cols, int $row, array $keys, array &$res, $LimitRow = null, $allowNull = false);

    /**
     * Require a populated cell in each listed column within the section.
     */
    protected function validateAtLeastOneCellOccupied(array $cols, $row, $message, $limitRow = null);

    /**
     * Require values for the specified number of rows and empty cells thereafter.
     */
    protected function CellsShouldNotBeEmptyRecursive($col, $row, $limitRow, $i = 1);

    /**
     * Apply each configured column's required row count.
     */
    protected function validateCellsShouldNotBeEmpty($limitRowArray);

    /**
     * Check cells through the inclusive last row, requiring values when reverse is enabled.
     */
    protected function CellsShouldBeEmptyRecursiveTillLimit($col, $row, $limit, $reverse = false);

    /**
     * Check the configured column ranges using their inclusive end rows.
     */
    protected function validateCellsShouldBeEmptyTillLimit($reverse = false);

    /**
     * Report empty sections using the label supplied with each row set.
     */
    protected function validateAllSectionsHaveAtLeastOneRow(...$data);

    /**
     * Report a start date later than the end date for the specified row.
     */
    private function validateFromToDateRecursive($fromDateCol, $toDateCol, $row);

    /**
     * Check the configured pairs of start and end date cells.
     */
    protected function validateFromToDate();

    /**
     * Compare worksheet rows with matching database rows and record missing rows and differing cells.
     */
    protected function validateExcelRowsMatchDatabase($dbRows, $ExcelRows, $cols, $startRow, $sectionName, $limitRow = null);

    /**
     * Update the supplied frequency map and report repeated values down a column.
     */
    public function validateUniqueColumnRecursive($col, $row, array &$freq);

    /**
     * Check each configured column for duplicates using a separate count per column.
     */
    public function validateUniqueColumns();

    /**
     * Compare consecutive cell values with increasing integers starting at the expected number.
     */
    private function validateNumbersAreSequentialRecursive($col, $row, $curNum = 1);

    /**
     * Require the column to count upwards from one at the given row.
     */
    public function validateNumbersAreSequential($col, $row);

    /**
     * Collect consecutive column values until the first missing or empty cell.
     */
    public function extractRecordsVales($col, $row);
}
```

---

### `Helpers\DateHelper`

**Purpose:**
Supply the default date and time format string.

```php
class DateHelper
{
    /**
     * Return the supplied format string or the package default.
     */
    public static function format(string $format = 'Y-m-d h:i A'): string;
}
```

---

### `ExceptionHelper`

**Purpose:**
Register JSON responses for exceptions in Laravel's exception configuration.

```php
class ExceptionHelper
{
    /**
     * Register API exception rendering logic.
     */
    public static function apiExceptionThrower(Exceptions &$exceptions): void;

    /**
     * Determine whether the current request expects a JSON response.
     */
    protected static function shouldReturnJson(): bool;
}
```

---

### `FileUploader`

**Purpose:**
Generate upload names and store, delete or locate files on a Laravel filesystem disk.

```php
class FileUploader
{
    /**
     * Generate a unique file name.
     */
    public static function generateFileName(
        UploadedFile $file,
        ?string $prefix = null
    ): string;

    /**
     * Upload a file to the specified disk and path.
     */
    public static function upload(
        ?UploadedFile $file,
        string $path,
        ?string $oldFile = null,
        ?string $prefix = null,
        string $disk = 'public'
    ): ?array;

    /**
     * Delete a file from the specified disk.
     */
    public static function delete(
        string $path,
        string $fileName,
        string $disk = 'public'
    ): bool;

    /**
     * Generate a publicly accessible URL for a stored file.
     */
    public static function url(
        string $path,
        ?string $fileName = null,
        string $disk = 'public',
        string $default = 'NoImg.jpg'
    ): string;
}
```

---

### `RateLimiterHelper`

**Purpose:**
Count attempts against a cache key and raise an HTTP 429 exception when the limit is reached.

```php
class RateLimiterHelper
{
    /**
     * Apply rate limiting for the given key.
     */
    public static function apply(
        string $key,
        int $maxAttempts,
        int $decaySeconds,
        string $message = 'Too many requests.',
        bool $disabled = false,
    ): void;
}
```

---

## Middleware

### `JwtMiddleware`

**Purpose:**
Authenticate a JWT for the selected guard and return a JSON error when authentication fails.

```php
class JwtMiddleware
{
    use JsonApiResponser;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $guard = 'user'): Response;
}
```

---

## SMTP Services

### `SmartMailSender`

**Purpose:**
Send a mailable using an SMTP account selected from the configured pool.

```php
class SmartMailSender
{
    /**
     * Acquire a lock for the recipient and mailable, then attempt delivery through the SMTP pool.
     */
    public static function send(Mailable $mailable, string $to): void;
}
```

---

### `SmtpConfigApplier`

**Purpose:**
Apply an account's connection settings to Laravel's SMTP mailer configuration.

```php
class SmtpConfigApplier
{
    /**
     * Set SMTP host, port, encryption and credentials, clearing credentials for Mailpit.
     */
    public static function apply(array $account): void;
}
```

---

### `SmtpPoolManager`

**Purpose:**
Select an SMTP account and track its daily send count in the cache.

```php
class SmtpPoolManager
{
    /**
     * Return Mailpit in testing mode or the first account below its daily limit, or null if exhausted.
     */
    public static function next(bool $isTesting = false, array $excludedAccounts = []): ?array;

    /**
     * Increment and return the account's cached send count for today.
     */
    public static function markSent(string $accountName);

    /**
     * Build the daily cache key for an account name.
     */
    private static function sentKey(string $name): string;
}
```

---

## Service Provider

### `AllInOneServiceProvider`

**Purpose:**
Register package configuration, console commands, the mail log channel and JWT middleware.

```php
class AllInOneServiceProvider extends ServiceProvider
{
    /**
     * Register console commands and publishable configuration, configure mail logging and alias the jwt middleware.
     */
    public function boot(): void;

    /**
     * Merge the package defaults into the application's all-in-one configuration.
     */
    public function register(): void;
}
```

---

## Project Structure

```text
src/
  AllInOneServiceProvider.php
  Console/
  Exceptions/
  Helpers/
  Middleware/
  Services/
  Services/Smtp/
  Traits/
  Traits/Eloquent/
```

---

## Who Is This For?

- Laravel developers building APIs or admin panels
- Teams that want consistent validation and response structures
- Projects that frequently implement filtering, searching, and sorting
- Developers who value clean abstractions over copy-pasted snippets

---

## Versioning

This package follows **semantic versioning**.

- `1.x` — Stable public API
- Minor versions may add new features
- Breaking changes will only occur in major versions

---

## Contributing

Contributions are welcome.

- Follow PSR-12 coding standards
- Write clear commit messages
- Add tests for new functionality when applicable

---

## License

Laravel All-In-One Toolkit is open-sourced software licensed under the MIT license.
