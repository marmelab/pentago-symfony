<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BoardService
{
    public const BOARD_LENGTH = 6;

    public function initBoard(): array
    {
        // Return a 6*6 2 dimensions array filled with 0 as value for each cell.
        return array_fill(0, $this->BOARD_LENGTH, array_fill(0, $this->BOARD_LENGTH, 0));
    }


    private function isOutsideBoard($position): bool
    {
        if ($position[0] < 0 || $position[0] >= $this->BOARD_LENGTH || $position[1] < 0 || $position[1] >= $this->BOARD_LENGTH) {
            return true;
        }

        return false;
    }

    public function isPositionAvailable($board, $position): bool
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
}
