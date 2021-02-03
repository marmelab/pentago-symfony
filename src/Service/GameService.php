<?php

namespace App\Service;

use App\Service\BoardService;
use App\Entity\Game;

class GameService
{
    public const ADD_MARBLE_STATUS = "add_marble";
    public const ROTATE_QUARTER_STATUS = "rotate_quarter";

    public const GAME_WAITING_OPPONENT = "waiting_opponent";
    public const GAME_STARTED = "started";

    private $boardService;

    public function __construct(BoardService $boardService)
    {
        $this->boardService = $boardService;
    }

    public function initGame(string $player1Hash): Game
    {
        $game = new Game();
        $game->setBoard($this->boardService->initBoard());
        $game->setTurnStatus(self::ADD_MARBLE_STATUS);
        $game->setStatus(self::GAME_WAITING_OPPONENT);
        $game->setPlayer1Hash($player1Hash);
        $game->setCurrentPlayerHash($player1Hash);

        return $game;
    }

    public function isStarted(Game $game): bool
    {
        return $game->getStatus() === self::GAME_STARTED;
    }

    // Add new player as player2 and let's go to start this game !
    public function setPlayer2AndStartGame(string $player2Hash, Game $game): Game
    {
        $game->setPlayer2Hash($player2Hash);
        $game->setStatus(self::GAME_STARTED);

        return $game;
    }

    // From a player hash, get if it's player1, 2 or null
    public function getPlayerValue(Game $game, ?string $playerHash): ?int
    {
        if ($game->getPlayer1Hash() === $playerHash) {
            return 1;
        }

        if ($game->getPlayer2Hash() === $playerHash) {
            return 2;
        }

        return null;
    }

    public function getCurrentPlayerValue(Game $game): int
    {
        return $this->getPlayerValue($game, $game->getCurrentPlayerHash());
    }

    // End of turn, switch to the other player.
    public function changeCurrentPlayerHash(Game $game): string
    {
        $currentPlayerValue = $this->getCurrentPlayerValue($game);

        return $currentPlayerValue === 1 ? $game->getPlayer2Hash() : $game->getPlayer1Hash();
    }

    public function addMarbleIfPositionIsValid(Game $game, array $position): Game
    {
        $board = $game->getBoard();

        if ($this->boardService->isPositionAvailable($board, $position) === true) {

            $value = $this->getCurrentPlayerValue($game);

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
        $game->setCurrentPlayerHash($this->changeCurrentPlayerHash($game));

        return $game;
    }
}
