<?php

namespace App\Dto;

use App\Entity\Game;

class GameDto
{

    public function __construct(Game $game)
    {
        $this->id = $game->getId();
        $this->currentPlayer = $game->getCurrentPlayer();
        $this->status = $game->getStatus();
        $this->winner = $game->getWinner();
        $this->allAlignedPositions = $game->getAllAlignedPositions();
        $this->turnStatus = $game->getTurnStatus();
    }
}
