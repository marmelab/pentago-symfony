<?php

namespace App\Entity;

use Symfony\Bridge\Doctrine\IdGenerator\UuidV4Generator;
use Doctrine\ORM\Mapping as ORM;

use App\Repository\GameRepository;

/**
 * @ORM\Entity(repositoryClass=GameRepository::class)
 */
class Game
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidV4Generator::class)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $player1_hash;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $player2_hash;

    /**
     * @ORM\Column(type="json")
     */
    private $board = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $turnStatus;

    /**
     * @ORM\Column(type="integer")
     */
    private $playerTurn;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer1Hash(): ?string
    {
        return $this->player1_hash;
    }

    public function setPlayer1Hash(?string $player1_hash): self
    {
        $this->player1_hash = $player1_hash;

        return $this;
    }

    public function getPlayer2Hash(): ?string
    {
        return $this->player2_hash;
    }

    public function setPlayer2Hash(?string $player2_hash): self
    {
        $this->player2_hash = $player2_hash;

        return $this;
    }

    public function getBoard(): ?array
    {
        return $this->board;
    }

    public function setBoard(array $board): self
    {
        $this->board = $board;

        return $this;
    }

    public function getTurnStatus(): ?string
    {
        return $this->turnStatus;
    }

    public function setTurnStatus(string $turnStatus): self
    {
        $this->turnStatus = $turnStatus;

        return $this;
    }

    public function getPlayerTurn(): ?int
    {
        return $this->playerTurn;
    }

    public function setPlayerTurn(int $playerTurn): self
    {
        $this->playerTurn = $playerTurn;

        return $this;
    }
}