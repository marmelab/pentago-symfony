<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BoardService
{
    public const BOARD_LENGTH = 6;
    public const HALF_BOARD_LENGTH = self::BOARD_LENGTH / 2;

    public function initBoard(): array
    {
        // Return a 6*6 2 dimensions array filled with 0 as value for each cell.
        return array_fill(0, self::BOARD_LENGTH, array_fill(0, self::BOARD_LENGTH, 0));
    }


    private function isOutsideBoard(array $position): bool
    {
        if ($position[0] < 0 || $position[0] >= self::BOARD_LENGTH || $position[1] < 0 || $position[1] >= self::BOARD_LENGTH) {
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

    public function addMarble($board, $position): array
    {
        $board[$position[0]][$position[1]] = 1;

        return $board;
    }

    private function getQuarterBoundaries(int $key): array
    {
        /*
            Each boundaries is an array containing boundaries for rows, and boundaries for columns.
        */
        switch ($key) {
            case 1:
                return array("start_row" => 0, "start_column" => self::HALF_BOARD_LENGTH);
            case 2:
                return array("start_row" => self::HALF_BOARD_LENGTH, "start_column" => self::HALF_BOARD_LENGTH);
            case 3:
                return array("start_row" => self::HALF_BOARD_LENGTH, "start_column" => 0);
            case 0:
            default:
                return array("start_row" => 0, "start_column" => 0);
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
        $rows = array_splice($board, $boundaries["start_row"], self::HALF_BOARD_LENGTH);

        // Loop over theses 3 rows an extract only 3 columns we need in our quarter
        foreach ($rows as $row) {
            $row_spliced = array_splice($row, $boundaries["start_column"], self::HALF_BOARD_LENGTH);
            // Push each spliced rows in the quarter.
            $quarter["matrix"][] = $row_spliced;
        }

        return $quarter;
    }

    public function setQuarterOnBoard(array $board, array $quarter): array
    {
        /*
            Replace a quarter on $board by the given $quarter
            return the updated board
        */

        $start_row = $quarter["boundaries"]["start_row"];
        $start_column = $quarter["boundaries"]["start_column"];


        foreach ($quarter["matrix"] as $index => $quarter_row) {
            $current_row = $start_row + $index;

            array_splice(
                $board[$current_row], // Get current row
                $start_column, // From the start column
                self::HALF_BOARD_LENGTH, // Remove 3 items
                $quarter_row // ...and replace them by the quarter row
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