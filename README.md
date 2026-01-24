- ~~ApiResponser Trait~~
- ~~JsonApiResponser Trait~~
- ~~CustomRule Traits~~
- ~~DateHelper Trait~~
- ~~EnumHelper Trait~~
- ~~ExcelFormatter Trait~~
- ~~FileHelper Trait~~
- Filterable Trait
- GenericHelper Trait
- MigrationHelper Trait
- ~~PaginatorHelper Trait~~
- ~~QueryParameter Trait~~
- Searchable Trait
- SeederHelper Trait
- Sortable Trait

- ~~JwtMiddleware Middleware~~

- ValidationErrorsAsArray	Exception
- ExceptionWithApiResponser Exception


- GitCommand Command
- DatabaseInitializerCommand Command

- CustomMigrationHelper Helper
- Date Helper
- Exception Helper
- FileUploader Helper
- Generic Helper


---

# API Reference (Purpose & Method Signatures)

> This document describes the **intended public API** of the package.
> Each class/trait and method includes a short description of **its responsibility and use case**.

---

## Console Commands

### `DatabaseInitialSeedersCommand`

**Purpose:**
Runs all initial database seeders in a controlled and automated way.

```php
class DatabaseInitialSeedersCommand
{
    /**
     * Initialize command dependencies.
     */
    public function __construct();

    /**
     * Execute all initial database seeders.
     */
    public function handle(): void;
}
```

---

### `GitCommand`

**Purpose:**
Provides Git-related automation tasks through Artisan.

```php
class GitCommand
{
    /**
     * Execute the Git command logic.
     */
    public function handle();
}
```

---

### `PhpMyAdminDatabaseTablesExtractorCommand`

**Purpose:**
Extracts database table definitions from phpMyAdmin SQL exports and processes them programmatically.

```php
class PhpMyAdminDatabaseTablesExtractorCommand
{
    /**
     * Initialize the extractor command.
     */
    public function __construct();

    /**
     * Handle SQL file extraction and processing.
     */
    public function handle(): void;
}
```

---

## Exceptions

### `ValidationErrorsAsArrayException`

**Purpose:**
Transforms validation errors into a structured array format suitable for API responses.

```php
class ValidationErrorsAsArrayException
{
    /**
     * Create a new validation exception instance.
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    );
}
```

---

## Traits (Core Helpers)

### `ApiResponser`

**Purpose:**
Provides a unified structure for API responses (success, error, pagination, messages).

```php
trait ApiResponser
{
    /**
     * Build a standardized successful API response.
     */
    protected function apiSuccess(
        mixed $data = null,
        ?string $message = null,
        array $meta = []
    ): array;

    /**
     * Build a standardized error API response.
     */
    protected function apiError(
        string $message,
        array $errors = [],
        array $meta = []
    ): array;

    /**
     * Build a paginated API response.
     */
    protected function apiPaginated(
        LengthAwarePaginator $paginator,
        ?string $message = null
    ): array;

    /**
     * Build a simple message-only API response.
     */
    protected function apiMessage(string $message): array;
}
```

---

### `JsonApiResponser`

**Purpose:**
Generates JSON responses compliant with HTTP status codes and API best practices.

```php
trait JsonApiResponser
{
    /**
     * Return a successful JSON response.
     */
    protected function jsonSuccess(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse;

    /**
     * Return an error JSON response.
     */
    protected function jsonError(
        string $message,
        int $status = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse;

    /**
     * Return a paginated JSON response.
     */
    protected function jsonPaginated(
        LengthAwarePaginator $paginator,
        ?string $message = null,
        int $status = 200
    ): JsonResponse;

    /**
     * Return a message-only JSON response.
     */
    protected function jsonMessage(
        string $message,
        int $status = 200
    ): JsonResponse;
}
```

---

### `CustomRule`

**Purpose:**
Simplifies the creation of reusable and expressive Laravel validation rules.

```php
trait CustomRule
{
    /**
     * Create a strong password validation rule.
     */
    public function strongPassword(
        int $min = 8,
        bool $hasMixed = true,
        bool $hasNumbers = true,
        bool $hasSymbols = true,
        bool $uncompromised = true
    ): Password;

    /**
     * Create an exists rule with optional conditions.
     */
    public function existsRule(
        string $table,
        string $column = 'id',
        ?Closure $whereClosure = null
    ): Exists;

    /**
     * Create a unique rule with optional ignore and conditions.
     */
    public function uniqueRule(
        string $table,
        string $column,
        ?Closure $whereClosure = null,
        mixed $ignoreId = null
    ): Unique;

    /**
     * Create an exists rule limited to active records.
     */
    public function existsActiveRule(
        string $table,
        string $column = 'id',
        string $activeColumn = 'is_active'
    ): Exists;

    /**
     * Create a unique rule scoped to a specific column/value.
     */
    public function uniqueScopedRule(
        string $table,
        string $column,
        string $scopeColumn,
        mixed $scopeValue,
        mixed $ignoreId = null
    ): Unique;
}
```

---

### `DateHelper`

**Purpose:**
Provides common date parsing, formatting, and comparison utilities using Carbon.

```php
trait DateHelper
{
    /**
     * Parse a date into a Carbon instance.
     */
    protected function parseDate(
        DateTimeInterface|string $date,
        string $timeZone = null
    ): Carbon;

    /**
     * Calculate the difference in years, months, and days.
     */
    public function diffInYearsMonthsDays(
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate
    ): array;

    /**
     * Check if a date is between two dates.
     */
    public function isBetweenDates(
        DateTimeInterface|string $date,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate
    ): bool;

    /**
     * Determine if a date is in the past.
     */
    public function isPastDate(DateTimeInterface|string $date): bool;

    /**
     * Determine if a date is in the future.
     */
    public function isFutureDate(DateTimeInterface|string $date): bool;

    /**
     * Convert a date to ISO format.
     */
    public function toIsoDate(DateTimeInterface|string $date): string;

    /**
     * Format a date using a custom format.
     */
    public function formatDate(
        DateTimeInterface|string $date,
        string $format = 'Y-m-d',
        string $timeZone = null
    ): ?string;

    /**
     * Calculate age from a birth date.
     */
    public function calculateAge(
        DateTimeInterface|string $birthDate
    ): int;

    /**
     * Add business days to a date.
     */
    public function addBusinessDays(
        DateTimeInterface|string $date,
        int $days
    ): Carbon;
}
```

---

### `ExcelFormatter`

**Purpose:**
Extracts and normalizes Excel data into PHP arrays.

```php
trait ExcelFormatter
{
    /**
     * Extract data from an uploaded Excel file.
     */
    public function excelFileExtractor(
        string $fileKey,
        bool $likeExcelCells = true,
        int $sheetIndex = 0,
        bool $skipEmptyCells = true
    ): array;
}
```

---

### `FileHelper`

**Purpose:**
Provides helper methods for working with files and file content.

```php
trait FileHelper
{
    /**
     * Read and decode JSON file contents.
     */
    private function getJsonFileContent(
        string $path,
        string $disk = 'local'
    ): array;
}
```

---

### `PaginatorHelper`

**Purpose:**
Enhances paginated results with additional metadata.

```php
trait PaginatorHelper
{
    /**
     * Add sequential row numbers to paginated results.
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
Resolves query string parameters into model data.

```php
trait QueryParameter
{
    /**
     * Resolve a single model from a query parameter.
     */
    public function resolveQueryModel(
        Request $request,
        string $keyName,
        string $model,
        string $column = 'id'
    ): ?array;

    /**
     * Resolve multiple models from a query parameter.
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

### Eloquent Traits (`Filterable`, `Searchable`, `Sortable`)

**Purpose:**
Provide reusable query scopes for filtering, searching, and sorting Eloquent models.

```php
trait FilterableTrait
{
    /**
     * Apply dynamic filtering to a query.
     */
    public function scopeFilter(
        Builder $q,
        string $filterColKey = 'filter_col',
        string $filterValKey = 'filter_val'
    ): Builder;
}
```

```php
trait SearchableTrait
{
    /**
     * Apply keyword search to a query.
     */
    public function scopeSearch(Builder $q): Builder;
}
```

```php
trait SortableTrait
{
    /**
     * Apply dynamic sorting to a query.
     */
    public function scopeSortByColumn(
        Builder $q,
        $target = null,
        $dir = null
    ): Builder;
}
```
