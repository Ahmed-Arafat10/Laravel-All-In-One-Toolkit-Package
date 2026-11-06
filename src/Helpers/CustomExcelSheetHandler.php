<?php

namespace AhmedArafat\AllInOne\Helpers;

use AhmedArafat\AllInOne\Traits\ExcelFormatter;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Validate and extract worksheet data indexed by Excel cell address, collecting errors for the calling handler.
 */
class CustomExcelSheetHandler
{
    use ExcelFormatter;


    protected array $titleCells;
    protected array $modelKeys;
    protected array $cellsShouldBeEmpty;
    protected array $cellsShouldBeEmptyTilLimit;

    protected array $requiredCells;
    protected array $uniqueColumns;

    protected array $nationalityEnum = [
        'Saudi Citizen',
        'Resident',
        'Foreigner',
    ];
    protected array $currencyEnum = [
        'USD',
        'SAR',
    ];
    protected array $genderEnum = [
        'Male',
        'Female'
    ];

    protected array $fromToDateCells;

    /**
     * Initialize the cell-address map and the list of validation errors.
     *
     * @param array<string, mixed> $excelSheet Values keyed by cell address, such as A1.
     * @param list<string> $errors Existing validation messages.
     */
    public function __construct(
        protected array $excelSheet,
        protected array $errors = []
    )
    {
    }

    /**
     * Convert an Excel serial date to a DateTime instance.
     *
     * @param float|int $excelDate
     * @return \DateTime
     */
    public static function convertExcelDate($excelDate)
    {
        return Date::excelToDateTimeObject($excelDate);
    }

    /**
     * Record missing titles and titles whose values differ from the configured text.
     *
     * @return void
     */
    protected function validateTitleCells()
    {
        foreach ($this->titleCells as $cell => $val) {
            //dump($this->excelSheet[$cell], $val);
            if (!isset($this->excelSheet[$cell])) $this->errors[] = "Cell $cell With Value `$val` Should Exists";
            else if ($this->excelSheet[$cell] != $val) $this->errors[] = "Cell $cell With Value `{$this->excelSheet[$cell]}` Should Have Value `$val`";
        }
    }

    /**
     * Check consecutive cells for emptiness, or require values when reverse is enabled.
     *
     * @param string $col
     * @param int $row
     * @param bool $reverse
     * @return void
     */
    protected function CellsShouldBeEmptyRecursive($col, $row, $reverse = false)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] != null && !$reverse) $this->errors[] = "Cell $cell Should Be Empty";
        else if ($this->excelSheet[$cell] == null && $reverse) $this->errors[] = "Cell $cell Should Not Be Empty";
        $this->CellsShouldBeEmptyRecursive($col, $row + 1, $reverse);
    }

    /**
     * Check each configured column starting at its specified row.
     *
     * @param bool $reverse
     * @return void
     */
    protected function validateCellsShouldBeEmpty($reverse = false)
    {
        foreach ($this->cellsShouldBeEmpty as $cell) {
            $this->CellsShouldBeEmptyRecursive($cell[0], $cell[1], $reverse);
        }
    }

    /**
     * Check nonempty values in consecutive cells against the allowed set.
     *
     * @param array $array
     * @param string $col
     * @param int $row
     * @return void
     */
    protected function hasEnumValuesRecursive($array, $col, $row)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if (!in_array($this->excelSheet[$cell], $array) && $this->excelSheet[$cell] != null) $this->errors[] = "Cell $cell Should Be One Of (" . implode(', ', $array) . ') Values';
        $this->hasEnumValuesRecursive($array, $col, $row + 1);
    }


    /**
     * Record nonnumeric values while walking the column from the given row.
     *
     * @param string $col
     * @param int $row
     * @return void
     */
    protected function mustBeNumeric($col, int $row)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] != null && !is_numeric($this->excelSheet[$cell])) $this->errors[] = "Value In Cell $cell Must Be Numeric";
        $this->mustBeNumeric($col, $row + 1);
    }

    /**
     * Check whether a value is numeric and meets the minimum Excel serial date of 36526.
     *
     * @param mixed $date
     * @return bool
     */
    private function isDate($date)
    {
        if (!is_numeric($date)) return false;
        else if ($date < 36526) return false;
        return true;
    }

    /**
     * Validate consecutive populated cells as Excel serial dates, stopping at the first empty cell.
     *
     * @param string $col
     * @param int $row
     * @return void
     */
    protected function mustBeDate($col, int $row)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] != null) {
            $dateCell = $this->excelSheet[$cell];
            //dd($dateCell); // 45302
            if (!$this->isDate($dateCell))
                $this->errors[] = "Value In Cell `$cell` Must Be Valid date in Y-m-d format.";
            $this->mustBeDate($col, $row + 1);
        }
    }


    /**
     * Record an error for each required cell that compares equal to null.
     *
     * @return void
     */
    protected function validateRequiredCells()
    {
        foreach ($this->requiredCells as $cell) {
            if ($this->excelSheet[$cell] == null)
                $this->errors[] = "Cell $cell Is Required";
        }
    }

    /**
     * Report partially filled rows until an empty row, missing cell or exclusive row limit.
     *
     * @param list<string> $cols
     * @param int $row
     * @param string $msg
     * @param int $i
     * @param int|null $limitRow
     * @return void
     */
    protected function validateWholeCellExists(array $cols, int $row, $msg, $i = 1, $limitRow = null)
    {
        if ($limitRow && $row == $limitRow) return;
        $cnt = 0;
        $cellsRequired = [];
        foreach ($cols as $col) {
            $cell = $col . $row;
            if (!array_key_exists($cell, $this->excelSheet)) return;
            if ($this->excelSheet[$cell] != null) $cnt++;
            else $cellsRequired[] = $cell;
        }
        if (!$cnt) return;
        if ($cnt != count($cols)) {
            $diff = count($cols) - $cnt;
            $singular = count($cellsRequired) == 1 ? 'Is' : 'Are';
            $addS = count($cellsRequired) != 1 ? 's' : '';
            $this->errors[] = "Cell$addS (" . implode(',', $cellsRequired) . ") $singular Required in Row $i in $msg"; // , So $diff Cell$addS $singular Required
        }
        $this->validateWholeCellExists($cols, $row + 1, $msg, $i + 1, $limitRow);
    }

    /**
     * Append rows to the result using the supplied keys, stopping at missing cells or the exclusive limit.
     *
     * @param list<string> $cols
     * @param int $row
     * @param list<string> $keys
     * @param array $res
     * @param int|null $LimitRow
     * @param bool $allowNull
     * @return array|null
     */
    protected function extractWholeRowsAsArray(array $cols, int $row, array $keys, array &$res, $LimitRow = null, $allowNull = false)
    {
        if ($LimitRow && $row == $LimitRow) return
            $temp = [];
        foreach ($cols as $idx => $col) {
            $cell = $col . $row;
            if (!array_key_exists($cell, $this->excelSheet)) return;
            $condition2 = !$allowNull ? $this->excelSheet[$cell] == null : 0;
            if ($condition2) return;
            $temp[$keys[$idx]] = $this->excelSheet[$cell];
        }
        $res[] = $temp;
        $this->extractWholeRowsAsArray($cols, $row + 1, $keys, $res, $LimitRow, $allowNull);
    }

    /**
     * Require a populated cell in each listed column within the section.
     *
     * @param list<string> $cols
     * @param int $row
     * @param string $message
     * @param int|null $limitRow
     * @return void
     */
    protected function validateAtLeastOneCellOccupied(array $cols, $row, $message, $limitRow = null)
    {
        foreach ($cols as $col) {
            $cnt = 0;
            $tempRow = $row;
            $cell = $col . $row;
            while (array_key_exists($cell, $this->excelSheet) && ($limitRow ? ($tempRow != $limitRow) : 1)) {
                if ($this->excelSheet[$cell] != null) $cnt++;
                $tempRow++;
                $cell = $col . $tempRow;
            }
            if (!$cnt) $this->errors[] = "At Least One Cell In Column $col Starting From Row $row Is Required in Section: $message";
        }
    }

    /**
     * Require values for the specified number of rows and empty cells thereafter.
     *
     * @param string $col
     * @param int $row
     * @param int|null $limitRow
     * @param int $i
     * @return void
     */
    protected function CellsShouldNotBeEmptyRecursive($col, $row, $limitRow, $i = 1)
    {
        $cell = $col . $row;
        $reachLimit = $limitRow < $i;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] == null && !$reachLimit) $this->errors[] = "Cell $cell Should Not Be Empty";
        if ($this->excelSheet[$cell] != null && $reachLimit) $this->errors[] = "Cell $cell Should Be Empty";
        $this->CellsShouldNotBeEmptyRecursive($col, $row + 1, $limitRow, $i + 1);
    }

    /**
     * Apply each configured column's required row count.
     *
     * @param array<int, int> $limitRowArray
     * @return void
     */
    protected function validateCellsShouldNotBeEmpty($limitRowArray)
    {
        foreach ($this->cellsShouldBeEmpty as $idx => $cell) {
            $this->CellsShouldNotBeEmptyRecursive($cell[0], $cell[1], $limitRowArray[$idx]);
        }
    }

    /**
     * Check cells through the inclusive last row, requiring values when reverse is enabled.
     *
     * @param string $col
     * @param int $row
     * @param int $limit
     * @param bool $reverse
     * @return void
     */
    protected function CellsShouldBeEmptyRecursiveTillLimit($col, $row, $limit, $reverse = false)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet) || $limit < $row) return;
        if ($this->excelSheet[$cell] != null && !$reverse) $this->errors[] = "Cell $cell Should Be Empty";
        else if ($this->excelSheet[$cell] == null && $reverse) $this->errors[] = "Cell $cell Should Not Be Empty";
        $this->CellsShouldBeEmptyRecursiveTillLimit($col, $row + 1, $limit, $reverse);
    }

    /**
     * Check the configured column ranges using their inclusive end rows.
     *
     * @param bool $reverse
     * @return void
     */
    protected function validateCellsShouldBeEmptyTillLimit($reverse = false)
    {
        foreach ($this->cellsShouldBeEmptyTilLimit as $cell) {
            $this->CellsShouldBeEmptyRecursiveTillLimit($cell[0], $cell[1], $cell[2], $reverse);
        }
    }

    /**
     * Report empty sections using the label supplied with each row set.
     *
     * @param array{0: array, 1: string} ...$data Row sets paired with their section labels.
     * @throws Exception When a section has no label.
     * @return void
     */
    protected function validateAllSectionsHaveAtLeastOneRow(...$data)
    {
        foreach ($data as $key => $item) {
            if (count($item) < 2) throw new Exception('Message For key {$key} Is Required');
            if (empty($item[0])) $this->errors[] = "At Least One Row Is Required In Section: {$item[1]}";
        }
    }

    /**
     * Report a start date later than the end date for the specified row.
     *
     * @param string $fromDateCol
     * @param string $toDateCol
     * @param int $row
     * @return void
     */
    private function validateFromToDateRecursive($fromDateCol, $toDateCol, $row)
    {

        $fromDateCell = $fromDateCol . $row;
        $toDateCell = $toDateCol . $row;
        $fromDate = $this->excelSheet[$fromDateCell];
        $toDate = $this->excelSheet[$toDateCell];

        if (!array_key_exists($fromDateCell, $this->excelSheet) || !array_key_exists($toDateCell, $this->excelSheet)) return;
        if ($this->isDate($fromDate) && $this->isDate($toDate)) {
            $fromDate = Date::excelToDateTimeObject($fromDate);
            $toDate = Date::excelToDateTimeObject($toDate);
            if ($fromDate->diff($toDate)->invert)
                $this->errors[] = "Date In Cell `{$fromDateCell}` Should Be Before Date In Cell `{$toDateCell}`";
        }
    }

    /**
     * Check the configured pairs of start and end date cells.
     *
     * @return void
     */
    protected function validateFromToDate()
    {
        foreach ($this->fromToDateCells as $item) {
            $this->validateFromToDateRecursive($item[0], $item[1], $item[2]);
        }
    }

    /**
     * Compare worksheet rows with matching database rows and record missing rows and differing cells.
     *
     * @param array $dbRows
     * @param array $ExcelRows
     * @param array $cols
     * @param int $startRow
     * @param string $sectionName
     * @param int|null $limitRow
     * @return void
     */
    protected function validateExcelRowsMatchDatabase($dbRows, $ExcelRows, $cols, $startRow, $sectionName, $limitRow = null)
    {
        $startRowTemp = $startRow;
        foreach ($ExcelRows as $i => $excelRow) {
            if ($limitRow && $startRow == $limitRow) return;
            if (!isset($dbRows[$i])) {
                $this->errors[] = "Entire Row Num. `$startRow`  In $sectionName Not Exists In Database";
                continue;
            }
            $colInNum = 0;
            foreach ($excelRow as $cell => $value) {
                if (!isset($cols[$colInNum])) break;
                $cellName = $cols[$colInNum] . $startRow;
                if ($dbRows[$i][$cell] != $value) {
                    $this->errors[] = "Value In Cell `$cellName` Expected To Be `{$dbRows[$i][$cell]}`, Found `{$value}`";
                }
                $colInNum++;
            }
            $startRow++;
        }
        if (count($dbRows) > count($ExcelRows)) {
            foreach ($dbRows as $i => $rows) {
                if ($i < count($ExcelRows)) continue;
                $r = $i + $startRowTemp;
                $this->errors[] = "Entire Row Num. `{$r}` Not Exists With Values (" . implode(',', $rows) . ') Respectively';
            }
        }
    }

    /**
     * Update the supplied frequency map and report repeated values down a column.
     *
     * @param string $col
     * @param int $row
     * @param array<array-key, int> $freq
     * @return void
     */
    public function validateUniqueColumnRecursive($col, $row, array &$freq)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        $freq[$this->excelSheet[$cell]] = isset($freq[$this->excelSheet[$cell]]) ?
            $freq[$this->excelSheet[$cell]] + 1
            : 1;
        if ($freq[$this->excelSheet[$cell]] >= 2)
            $this->errors[] = "Value ({$this->excelSheet[$cell]}) In Cell `$cell` Is Repeated, You Should Change It";
        $this->validateUniqueColumnRecursive($col, $row + 1, $freq);
    }

    /**
     * Check each configured column for duplicates using a separate count per column.
     *
     * @return void
     */
    public function validateUniqueColumns()
    {
        foreach ($this->uniqueColumns as $item) {
            $freq = [];
            $this->validateUniqueColumnRecursive($item[0], $item[1], $freq);
        }
    }

    /**
     * Compare consecutive cell values with increasing integers starting at the expected number.
     *
     * @param string $col
     * @param int $row
     * @param int $curNum
     * @return void
     */
    private function validateNumbersAreSequentialRecursive($col, $row, $curNum = 1)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] != $curNum)
            $this->errors[] = "Number In Cell `$cell` Must Be Number `$curNum` Found `{$this->excelSheet[$cell]}`";
        $this->validateNumbersAreSequentialRecursive($col, $row + 1, $curNum + 1);
    }

    /**
     * Require the column to count upwards from one at the given row.
     *
     * @param string $col
     * @param int $row
     * @return void
     */
    public function validateNumbersAreSequential($col, $row)
    {
        $this->validateNumbersAreSequentialRecursive($col, $row);
    }

    /**
     * Collect consecutive column values until the first missing or empty cell.
     *
     * @param string $col
     * @param int $row
     * @return list<mixed>
     */
    public function extractRecordsVales($col, $row)
    {
        $cell = $col . $row;
        $values = [];
        while (array_key_exists($cell, $this->excelSheet) && $this->excelSheet[$cell] != null) {
            $values[] = $this->excelSheet[$cell];
            $cell = $col . ++$row;
        }
        return $values;
    }
}
