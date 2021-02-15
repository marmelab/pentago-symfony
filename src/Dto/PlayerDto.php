<?php

namespace App\Dto;

use App\Entity\Player;

class PlayerDto{

    public $id;
    public $name;

    public function __construct(Player $player) {
        $this->id = $player->getId();
        $this->name = $player->getName();
    }
}
