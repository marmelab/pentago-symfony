<?php

namespace App\Service;

use App\Service\BoardService;

class WinDetectionService
{
    public const WIN_CONDITION = 5;
    public const WIN_CHECK_AREA = 2;

    public function __construct(BoardService $boardService)
    {
        $this->boardService = $boardService;
    }


    public function browseBoardInDirectionToGetAligmnent(array $board, array $startPosition, array $direction): ?array
    {
        if ($this->boardService->isOutsideBoard($startPosition)) {
            return null;
        }

        // Get start position value
        $startValue = $board[$startPosition[0]][$startPosition[1]];

        // If it's empty
        if ($startValue === 0) {
            return null;
        }

        // It's filled by a player, it can be an alignment.
        $alignedPositions[] = $startPosition;

        // We need to loop 4 next values in direction.
        for ($i = 1; $i < self::WIN_CONDITION; $i++) {

            // Check the next position
            $currentPosition = array(
                $startPosition[0] + ($i * $direction[0]),
                $startPosition[1] + ($i * $direction[1])
            );

            if ($this->boardService->isOutsideBoard($currentPosition)) {
                return null;
            }

            $value = $board[$currentPosition[0]][$currentPosition[1]];

            // If it's another player (or empty), it canno't be an alignment
            if ($value !== $startValue) {
                return null;
            }

            // It's another player combination, save it.
            $alignedPositions[] = $currentPosition;
        }

        // At this step, we found 5 aligned marbles.
        return array(
            "player" => $startValue,
            "alignedPositions" => $alignedPositions
        );
    }

    public function loopOverAllPossibilitiesForAlignmentInDirection(array $board, array $rangeRow, array $rangeCol, array $direction): array
    {
        $results = array();

        foreach ($rangeRow as $row) {
            foreach ($rangeCol as $col) {
                $startPosition = array($row, $col);

                $result = $this->browseBoardInDirectionToGetAligmnent($board, $startPosition, $direction);

                if ($result) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }


    public function getAllLinesAligned(array $board): array
    {
        /*
        To check if we have a 5 marbles aligned in rows, we only have to iterate throught first two columns.
        ┌─────────+─────────┐
        | x  x  ◯ | ◯  ◯  ◯ |
        | x  x  ◯ | ◯  ◯  ◯ |
        | x  x  ◯ | ◯  ◯  ◯ |
        |─────────+─────────|
        | x  x  ◯ | ◯  ◯  ◯ |
        | x  x  ◯ | ◯  ◯  ◯ |
        | x  x  ◯ | ◯  ◯  ◯ |
        └─────────+─────────┘
        */

        $rangeRow = range(0, $this->boardService::BOARD_LENGTH);
        $rangeCol = range(0, self::WIN_CHECK_AREA);
        $direction = array(0, 1);
        return $this->loopOverAllPossibilitiesForAlignmentInDirection($board, $rangeRow, $rangeCol, $direction);
    }

    public function getAllColumnsAligned(array $board): array
    {
        /*
        To check if we have a column with 5 marbles aligned, we only have to iterate throught first two lines:

        ┌─────────+─────────┐
        | x  x  x | x  x  x |
        | x  x  x | x  x  x |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        |─────────+─────────|
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        └─────────+─────────┘
        */
        $rangeRow = range(0, self::WIN_CHECK_AREA);
        $rangeCol = range(0, $this->boardService::BOARD_LENGTH);
        $direction = array(1, 0);
        return $this->loopOverAllPossibilitiesForAlignmentInDirection($board, $rangeRow, $rangeCol, $direction);
    }

    public function getAllDiagonalesAligned(array $board): array
    {
        /*
        To check if we have a 5 marbles aligned in diagonales (from top-left to right-bottom), we only have to check following start positions.

        ┌─────────+─────────┐
        | x  x  ◯ | ◯  ◯  ◯ |
        | x  x  ◯ | ◯  ◯  ◯ |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        |─────────+─────────|
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        └─────────+─────────┘
        */

        $rangeRow = range(0, self::WIN_CHECK_AREA);
        $rangeCol = range(0, self::WIN_CHECK_AREA);
        $direction = array(1, 1);

        return $this->loopOverAllPossibilitiesForAlignmentInDirection($board, $rangeRow, $rangeCol, $direction);
    }

    public function getAllReversedDiagonalesAligned(array $board): array
    {
        /*
        Finally, to check if we have a 5 marbles aligned in reversed diagonales (from top-right to left-bottom), we only have to check following start positions.
        ┌─────────+─────────┐
        | ◯  ◯  ◯ | ◯  x  x |
        | ◯  ◯  ◯ | ◯  x  x |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        |─────────+─────────|
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        | ◯  ◯  ◯ | ◯  ◯  ◯ |
        └─────────+─────────┘
        */

        $rangeRow = range(0, self::WIN_CHECK_AREA);
        $rangeCol = range($this->boardService::BOARD_LENGTH - self::WIN_CHECK_AREA, $this->boardService::BOARD_LENGTH);
        $direction = array(1, -1);

        return $this->loopOverAllPossibilitiesForAlignmentInDirection($board, $rangeRow, $rangeCol, $direction);
    }

    public function getAllMarblesCombinationsCorrectlyAligned(array $board): array
    {
        /*
        The board is a 6*6 board.

        To check if we have 5 marbles aligned, we only need to start checking from positions described below:
        ┌─────────+─────────┐
        | x  x  x | x  x  x |
        | x  x  x | x  x  x |
        | x  x  ◯ | ◯  ◯  ◯ |
        |─────────+─────────|
        | x  x  ◯ | ◯  ◯  ◯ |
        | x  x  ◯ | ◯  ◯  ◯ |
        | x  x  ◯ | ◯  ◯  ◯ |
        └─────────+─────────┘

        And browse the board to the right, to the bottom,
        to the right bottom (diagonale) and to the left bottom (reversed-diagonale)
        */

        $alignments = array_merge(
            $this->getAllLinesAligned($board),
            $this->getAllColumnsAligned($board),
            $this->getAllDiagonalesAligned($board),
            $this->getAllReversedDiagonalesAligned($board)
        );

        $results = array(
            "winners" => array(),
            "allAlignedPositions" => array()
        );

        foreach ($alignments as $alignment) {
            $results["winners"][$alignment["player"]] = true;
            $results["allAlignedPositions"] = array_merge($results["allAlignedPositions"], $alignment["alignedPositions"]);
        }

        return $results;
    }
}
