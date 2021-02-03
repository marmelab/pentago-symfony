<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;

use App\Service\WinDetectionService;
use App\Service\BoardService;

class WinDetectionTest extends TestCase
{
    /**
     * @dataProvider provideBoards
     */
    public function testGetAllMarblesCombinationsCorrectlyAligned($board, $expectedResult)
    {
        $boardService = new BoardService();

        $winDectectionService = new WinDetectionService($boardService);

        $result = $winDectectionService->getAllMarblesCombinationsCorrectlyAligned($board);
        // assert that your calculator added the numbers correctly!
        $this->assertEquals($result, $expectedResult);
    }

    public function provideBoards()
    {
        $boardService = new BoardService();

        $boardLine = $boardService->initBoard();
        $boardLine[3][1] = 1;
        $boardLine[3][2] = 1;
        $boardLine[3][3] = 1;
        $boardLine[3][4] = 1;
        $boardLine[3][5] = 1;
        $expectedResultLine = [
            "winners" => [
                1 => true
            ],
            "allAlignedPositions" => [[3, 1], [3, 2], [3, 3], [3, 4], [3, 5]]
        ];

        $boardColumn = $boardService->initBoard();
        $boardColumn[1][0] = 1;
        $boardColumn[2][0] = 1;
        $boardColumn[3][0] = 1;
        $boardColumn[4][0] = 1;
        $boardColumn[5][0] = 1;
        $expectedResultColumn = [
            "winners" => [
                1 => true
            ],
            "allAlignedPositions" => [[1, 0], [2, 0], [3, 0], [4, 0], [5, 0]]
        ];

        $boardDiagonale = $boardService->initBoard();
        $boardDiagonale[1][1] = 1;
        $boardDiagonale[2][2] = 1;
        $boardDiagonale[3][3] = 1;
        $boardDiagonale[4][4] = 1;
        $boardDiagonale[5][5] = 1;
        $expectedResultDiagonale = [
            "winners" => [
                1 => true
            ],
            "allAlignedPositions" => [[1, 1], [2, 2], [3, 3], [4, 4], [5, 5]]
        ];

        $boardReversedDiagonale = $boardService->initBoard();
        $boardReversedDiagonale[0][5] = 1;
        $boardReversedDiagonale[1][4] = 1;
        $boardReversedDiagonale[2][3] = 1;
        $boardReversedDiagonale[3][2] = 1;
        $boardReversedDiagonale[4][1] = 1;
        $expectedResultReversedDiagonale = [
            "winners" => [
                1 => true
            ],
            "allAlignedPositions" => [[0, 5], [1, 4], [2, 3], [3, 2], [4, 1]]
        ];

        $boardMultiple = $boardService->initBoard();
        $boardMultiple[1][0] = 2;
        $boardMultiple[2][0] = 2;
        $boardMultiple[3][0] = 2;
        $boardMultiple[4][0] = 2;
        $boardMultiple[5][0] = 2;
        $boardMultiple[0][5] = 1;
        $boardMultiple[1][4] = 1;
        $boardMultiple[2][3] = 1;
        $boardMultiple[3][2] = 1;
        $boardMultiple[4][1] = 1;
        $expectedResultMultiple = [
            "winners" => [
                1 => true,
                2 => true
            ],
            "allAlignedPositions" => [[1, 0], [2, 0], [3, 0], [4, 0], [5, 0], [0, 5], [1, 4], [2, 3], [3, 2], [4, 1]]
        ];

        return [
            [$boardLine, $expectedResultLine],
            [$boardColumn, $expectedResultColumn],
            [$boardDiagonale, $expectedResultDiagonale],
            [$boardReversedDiagonale, $expectedResultReversedDiagonale],
            [$boardMultiple, $expectedResultMultiple]
        ];
    }
}
