<?php

namespace App\Service;

use App\Service\BoardService;
use App\Entity\Game;

class GameService
{
    public const ADD_MARBLE_STATUS = "add_marble";
    public const ROTATE_QUARTER_STATUS = "rotate_quarter";

    private $boardService;

    public function __construct(BoardService $boardService)
    {
        $this->boardService = $boardService;
    }

    public function initGame(): Game
    {
        $game = new Game();
        $game->setBoard($this->boardService->initBoard());
        $game->setTurnStatus(self::ADD_MARBLE_STATUS);
        $game->setPlayerTurn(1);

        return $game;
    }

    public function changePlayerTurn(int $playerTurn): int
    {
        return $playerTurn === 1 ? 2 : 1;
    }



    public function addMarbleIfPositionIsValid(Game $game, array $position, int $value): Game
    {
        $board = $game->getBoard();

        if ($this->boardService->isPositionAvailable($board, $position) === true) {
            $board = $this->boardService->addMarble($board, $position, $value);

            // Update board in the game with the new one.
            $game->setBoard($board);

            // Change step to rotation quarter
            $game->setTurnStatus(self::ROTATE_QUARTER_STATUS);
        }

        return $game;
    }

    public function rotateQuarterBy90Degrees(Game $game, int $rotationKey): Game
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

        $board = $game->getBoard();

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
        $board = $this->boardService->setQuarterOnBoard($board, $quarter);
        $game->setBoard($board);

        // Change step to add marble.
        $game->setTurnStatus((self::ADD_MARBLE_STATUS));

        // Player has finished his turn
        $game->setPlayerTurn($this->changePlayerTurn($game->getPlayerTurn()));

        return $game;
    }
}
