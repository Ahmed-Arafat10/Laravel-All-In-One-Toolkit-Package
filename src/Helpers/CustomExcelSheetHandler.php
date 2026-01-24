<?php

namespace AhmedArafat\AllInOne\Helpers;

use AhmedArafat\AllInOne\Traits\ExcelFormatter;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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

    public function __construct(
        protected array $excelSheet,
        protected array $errors = []
    )
    {
    }

    public static function convertExcelDate($excelDate)
    {
        return Date::excelToDateTimeObject($excelDate);
    }

    protected function validateTitleCells()
    {
        foreach ($this->titleCells as $cell => $val) {
            //dump($this->excelSheet[$cell], $val);
            if (!isset($this->excelSheet[$cell])) $this->errors[] = "Cell $cell With Value `$val` Should Exists";
            else if ($this->excelSheet[$cell] != $val) $this->errors[] = "Cell $cell With Value `{$this->excelSheet[$cell]}` Should Have Value `$val`";
        }
    }

    protected function CellsShouldBeEmptyRecursive($col, $row, $reverse = false)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] != null && !$reverse) $this->errors[] = "Cell $cell Should Be Empty";
        else if ($this->excelSheet[$cell] == null && $reverse) $this->errors[] = "Cell $cell Should Not Be Empty";
        $this->CellsShouldBeEmptyRecursive($col, $row + 1, $reverse);
    }

    protected function validateCellsShouldBeEmpty($reverse = false)
    {
        foreach ($this->cellsShouldBeEmpty as $cell) {
            $this->CellsShouldBeEmptyRecursive($cell[0], $cell[1], $reverse);
        }
    }

    protected function hasEnumValuesRecursive($array, $col, $row)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if (!in_array($this->excelSheet[$cell], $array) && $this->excelSheet[$cell] != null) $this->errors[] = "Cell $cell Should Be One Of (" . implode(', ', $array) . ') Values';
        $this->hasEnumValuesRecursive($array, $col, $row + 1);
    }


    protected function mustBeNumeric($col, int $row)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] != null && !is_numeric($this->excelSheet[$cell])) $this->errors[] = "Value In Cell $cell Must Be Numeric";
        $this->mustBeNumeric($col, $row + 1);
    }

    private function isDate($date)
    {
        if (!is_numeric($date)) return false;
        else if ($date < 36526) return false;
        return true;
    }

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


    protected function validateRequiredCells()
    {
        foreach ($this->requiredCells as $cell) {
            if ($this->excelSheet[$cell] == null)
                $this->errors[] = "Cell $cell Is Required";
        }
    }

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

    protected function CellsShouldNotBeEmptyRecursive($col, $row, $limitRow, $i = 1)
    {
        $cell = $col . $row;
        $reachLimit = $limitRow < $i;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] == null && !$reachLimit) $this->errors[] = "Cell $cell Should Not Be Empty";
        if ($this->excelSheet[$cell] != null && $reachLimit) $this->errors[] = "Cell $cell Should Be Empty";
        $this->CellsShouldNotBeEmptyRecursive($col, $row + 1, $limitRow, $i + 1);
    }

    protected function validateCellsShouldNotBeEmpty($limitRowArray)
    {
        foreach ($this->cellsShouldBeEmpty as $idx => $cell) {
            $this->CellsShouldNotBeEmptyRecursive($cell[0], $cell[1], $limitRowArray[$idx]);
        }
    }

    protected function CellsShouldBeEmptyRecursiveTillLimit($col, $row, $limit, $reverse = false)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet) || $limit < $row) return;
        if ($this->excelSheet[$cell] != null && !$reverse) $this->errors[] = "Cell $cell Should Be Empty";
        else if ($this->excelSheet[$cell] == null && $reverse) $this->errors[] = "Cell $cell Should Not Be Empty";
        $this->CellsShouldBeEmptyRecursiveTillLimit($col, $row + 1, $limit, $reverse);
    }

    protected function validateCellsShouldBeEmptyTillLimit($reverse = false)
    {
        foreach ($this->cellsShouldBeEmptyTilLimit as $cell) {
            $this->CellsShouldBeEmptyRecursiveTillLimit($cell[0], $cell[1], $cell[2], $reverse);
        }
    }

    protected function validateAllSectionsHaveAtLeastOneRow(...$data)
    {
        foreach ($data as $key => $item) {
            if (count($item) < 2) throw new Exception('Message For key {$key} Is Required');
            if (empty($item[0])) $this->errors[] = "At Least One Row Is Required In Section: {$item[1]}";
        }
    }

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

    protected function validateFromToDate()
    {
        foreach ($this->fromToDateCells as $item) {
            $this->validateFromToDateRecursive($item[0], $item[1], $item[2]);
        }
    }

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

    public function validateUniqueColumns()
    {
        foreach ($this->uniqueColumns as $item) {
            $freq = [];
            $this->validateUniqueColumnRecursive($item[0], $item[1], $freq);
        }
    }

    private function validateNumbersAreSequentialRecursive($col, $row, $curNum = 1)
    {
        $cell = $col . $row;
        if (!array_key_exists($cell, $this->excelSheet)) return;
        if ($this->excelSheet[$cell] != $curNum)
            $this->errors[] = "Number In Cell `$cell` Must Be Number `$curNum` Found `{$this->excelSheet[$cell]}`";
        $this->validateNumbersAreSequentialRecursive($col, $row + 1, $curNum + 1);
    }

    public function validateNumbersAreSequential($col, $row)
    {
        $this->validateNumbersAreSequentialRecursive($col, $row);
    }

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
