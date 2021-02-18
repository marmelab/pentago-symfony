<?php

namespace App\Service;

use App\Service\BoardService;
use App\Service\WinDetectionService;
use App\Entity\Game;
use App\Entity\Player;

class GameService
{
    public const ADD_MARBLE_STATUS = "add_marble";
    public const ROTATE_QUARTER_STATUS = "rotate_quarter";

    public const GAME_WAITING_OPPONENT = "waiting_opponent";
    public const GAME_STARTED = "started";
    public const GAME_FINISHED = "finished";

    private $boardService;

    public function __construct(BoardService $boardService, WinDetectionService $winDetectionService)
    {
        $this->boardService = $boardService;
        $this->winDetectionService = $winDetectionService;
    }

    public function initGame(Player $player, $againstComputer): Game
    {
        $game = new Game();
        $game->setBoard($this->boardService->initBoard());
        $game->setTurnStatus(self::ADD_MARBLE_STATUS);
        $game->setPlayer1($player);
        $game->setAgainstComputer($againstComputer);

        if (!$againstComputer) {
            $game->setStatus(self::GAME_WAITING_OPPONENT);
        } else {
            $game->setCurrentPlayer($game->getPlayer1());
            $game->setStatus(self::GAME_STARTED);
        }
        

        return $game;
    }

    public function isStarted(Game $game): bool
    {
        return $game->getStatus() === self::GAME_STARTED;
    }

    // Add new player as player2 and let's go to start this game !
    public function setPlayer2AndStartGame(Player $player, Game $game): Game
    {
        $game->setPlayer2($player);
        $randomNumber = rand(0, 1);
        if ($randomNumber === 0) {
            $game->setCurrentPlayer($game->getPlayer1());
        } else {
            $game->setCurrentPlayer($game->getPlayer2());
        }
        $game->setStatus(self::GAME_STARTED);
        return $game;
    }

    // From a player hash, get if it's player1, 2 or null
    public function getCurrentPlayerValue(Game $game): ?int
    {
        if ($game->getPlayer1() === $game->getCurrentPlayer()) {
            return 1;
        }

        if (!$game->getCurrentPlayer() || $game->getPlayer2() === $game->getCurrentPlayer()) {
            return 2;
        }

        return null;
    }

    // End of turn, switch to the other player.
    public function changeCurrentPlayer(Game $game): ?Player
    {
        if ($game->getAgainstComputer()) {
            return $game->getCurrentPlayer() === $game->getPlayer1() ? NULL : $game->getPlayer1();
        }

        return $game->getCurrentPlayer() === $game->getPlayer1() ? $game->getPlayer2() : $game->getPlayer1();
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
        $game->setCurrentPlayer($this->changeCurrentPlayer($game));

        // At this step we have also to check if game is finished.
        $game = $this->checkIfGameIsFinished($game);

        return $game;
    }

    public function checkIfGameIsFinished(Game $game): Game
    {
        $results = $this->winDetectionService->getAllMarblesCombinationsCorrectlyAligned($game->getBoard());

        $numberOfWinners = count($results["winners"]);
        if ($numberOfWinners > 0) {
            $game->setStatus(self::GAME_FINISHED);
            $game->setAllAlignedPositions($results["allAlignedPositions"]);

            $winner = $numberOfWinners === 1 ? array_key_first($results["winners"]) : 0;
            $game->setWinner($winner);
        }

        return $game;
    }

    public function getRotationKeyFromIA($rotation): int {
        switch(intval($rotation[0])) {
            case 1:
            default:
                return $rotation[1] === "counter-clockwise" ? 0 : 1;
            case 2:
                return $rotation[1] === "counter-clockwise" ? 2 : 3;
            case 3:
                return $rotation[1] === "counter-clockwise" ? 4 : 5;
            case 4:
                return $rotation[1] === "counter-clockwise" ? 6 : 7;
                
        }
    }
}
