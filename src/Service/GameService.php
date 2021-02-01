<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\BoardService;

class GameService
{
    public const ADD_MARBLE_STATUS = "add_marble";
    public const ROTATE_QUARTER_STATUS = "rotate_quarter";

    private $boardService;

    public function __construct(BoardService $boardService)
    {
        $this->boardService = $boardService;
    }

    public function initGame(): array
    {
        return array(
            "board" => $this->boardService->initBoard(),
            "turnStatus" => self::ADD_MARBLE_STATUS
        );
    }



    public function addMarbleIfPositionIsValid(array $game, array $position): array
    {
        $board = $game["board"];

        if ($this->boardService->isPositionAvailable($board, $position) === true) {
            $board = $this->boardService->addMarble($board, $position);

            // Update board in the game with the new one.
            $game["board"] = $board;

            // Change step to rotation quarter
            $game["turnStatus"] = self::ROTATE_QUARTER_STATUS;
        }

        return $game;
    }

    public function rotateQuarterBy90Degrees(array $game, int $rotationKey): array
    {
        /*
        Schema of the board with quarters and rotation keys :
            1 ↻  2 ↺
        0 ↺ ┌───+───┐  3 ↻
            | 1 | 2 |
            |───+───|
            | 4 | 3 |
        7 ↻ └───+───┘ 4 ↺
            6 ↺  5 ↻ 
        */

        $board = $game["board"];

        /* Rotation key is an integer between 0 and 7.
            odd means a counter clockwise rotation.
            Even is clockwise.
            $direction is defined by a % 2 operation.
        */
        $direction = $rotationKey % 2 == 0 ? -1 : 1;

        /* 
            From the board, extract one of the quarter by giving rotationKey
            Each quarter have 2 rotations key, to determine which quarter we choose, use euclidean division.
            $quarter will contain a 3*3 matrix + boundaries where he come from.
            +───┐
            | 2 |
            +───|

            */
        $quarter = $this->boardService->getQuarter($board, intdiv($rotationKey, 2));

        /*
            Rotate a matrix by 90deg in direction
            +───┐      +───┐
            |👆 | ===> | 👉|
            +───|      +───|
        */
        $quarter["matrix"] = $this->boardService->rotateMatrix90Degree($quarter["matrix"], $direction);

        /*
            Finally, set on the board the rotated quarter
            ┌───+───┐
            | 1 | 👉|
            |───+───|
            | 4 | 3 |
            └───+───┘
        */
        $game["board"] = $this->boardService->setQuarterOnBoard($board, $quarter);

        // Change step to add marble.
        $game["turnStatus"] = self::ADD_MARBLE_STATUS;

        return $game;
    }
}
