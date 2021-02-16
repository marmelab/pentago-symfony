<?php

namespace App\Service;

class BoardService
{
    public const BOARD_LENGTH = 6;
    public const HALF_BOARD_LENGTH = self::BOARD_LENGTH / 2;

    public function initBoard(): array
    {
        // Return a 6*6 2 dimensions array filled with 0 as value for each cell.
        return array_fill(0, self::BOARD_LENGTH, array_fill(0, self::BOARD_LENGTH, 0));
    }


    public function isOutsideBoard(array $position): bool
    {
        if ($position[0] < 0 ||
            $position[0] >= self::BOARD_LENGTH ||
            $position[1] < 0 ||
            $position[1] >= self::BOARD_LENGTH) {
            return true;
        }

        return false;
    }

    public function isPositionAvailable(array $board, array $position): bool
    {
        if ($this->isOutsideBoard($position)) {
            return false;
        }

        if ($board[$position[0]][$position[1]] !== 0) {
            return false;
        }

        return true;
    }

    public function addMarble(array $board, array $position, int $value): array
    {
        $board = array_merge_recursive(array(), $board); // Make a deep copy to immutability
        $board[$position[0]][$position[1]] = $value;

        return $board;
    }

    private function getQuarterBoundaries(int $key): array
    {
        /*
            Each boundaries is an array containing boundaries for rows, and boundaries for columns.
        */
        switch ($key) {
            case 1:
                return array("startRow" => 0, "startColumn" => self::HALF_BOARD_LENGTH);
            case 3:
                return array("startRow" => self::HALF_BOARD_LENGTH, "startColumn" => self::HALF_BOARD_LENGTH);
            case 2:
                return array("startRow" => self::HALF_BOARD_LENGTH, "startColumn" => 0);
            case 0:
            default:
                return array("startRow" => 0, "startColumn" => 0);
        }
    }

    public function getQuarter(array $board, int $key): array
    {
        $boundaries = $this->getQuarterBoundaries($key);

        // Save boundaries because we will need them to reapply quarter at same locations.
        $quarter = array(
            "matrix" => [],
            "boundaries" => $boundaries
        );

        // Get only 3 rows we need for ou quarter
        $rows = array_splice($board, $boundaries["startRow"], self::HALF_BOARD_LENGTH);

        // Loop over theses 3 rows an extract only 3 columns we need in our quarter
        foreach ($rows as $row) {
            $rowSpliced = array_splice($row, $boundaries["startColumn"], self::HALF_BOARD_LENGTH);
            // Push each spliced rows in the quarter.
            $quarter["matrix"][] = $rowSpliced;
        }

        return $quarter;
    }

    public function setQuarterOnBoard(array $board, array $quarter): array
    {
        /*
            Replace a quarter on $board by the given $quarter
            return the updated board
        */
        $board = array_merge_recursive(array(), $board); // Make a deep copy to immutability

        $startRow = $quarter["boundaries"]["startRow"];
        $startColumn = $quarter["boundaries"]["startColumn"];


        foreach ($quarter["matrix"] as $index => $quarterRow) {
            $currentRow = $startRow + $index;

            array_splice(
                $board[$currentRow], // Get current row
                $startColumn, // From the start column
                self::HALF_BOARD_LENGTH, // Remove 3 items
                $quarterRow // ...and replace them by the quarter row
            );
        }
        return $board;
    }

    public function rotateMatrix90Degree(array $matrix, int $direction = 1): array
    {
        /*
            If you reverse a matrix and tranpose it, you will obtain a -90deg rotation (counter clockwise).
            If you transpose it first and reverse it after, you will obtain a 90deg rotation (clockwise).
        */
        if ($direction === -1) {
            $matrix = array_map('array_reverse', $matrix);
            $matrix = array_map(null, ...$matrix); // Transpose a matrix
        } else {
            $matrix = array_map(null, ...$matrix);
            $matrix = array_map('array_reverse', $matrix); // Transpose a matrix
        }

        return $matrix;
    }
}
