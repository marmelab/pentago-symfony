<?php

namespace App\Entity;

use Symfony\Bridge\Doctrine\IdGenerator\UuidV4Generator;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;
use App\Repository\GameRepository;
use DateTime;
use Symfony\Component\Uid\UuidV4;

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
     * @ORM\ManyToOne(targetEntity="Player", cascade={"all"}, fetch="EAGER")
     */
    private $player1;

    /**
     * @ORM\ManyToOne(targetEntity="Player", cascade={"all"}, fetch="EAGER")
     */
    private $player2;

    /**
     * @ORM\Column(type="json")
     */
    private $board = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $turnStatus;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="Player", cascade={"all"}, fetch="EAGER")
     */
    private $currentPlayer;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $allAlignedPositions = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $winner;

    /**
     * @Gedmo\Timestampable(on="create")
     * @Doctrine\ORM\Mapping\Column(type="datetime")
     */
    private $created;

    public function getId(): ?UuidV4
    {
        return $this->id;
    }

    public function getPlayer1(): ?Player
    {
        return $this->player1;
    }

    public function setPlayer1(?Player $player1): self
    {
        $this->player1 = $player1;

        return $this;
    }

    public function getPlayer2(): ?Player
    {
        return $this->player2;
    }

    public function setPlayer2(?Player $player2): self
    {
        $this->player2 = $player2;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCurrentPlayer(): ?Player
    {
        return $this->currentPlayer;
    }

    public function setCurrentPlayer(?Player $currentPlayer): self
    {
        $this->currentPlayer = $currentPlayer;

        return $this;
    }

    public function getAllAlignedPositions(): ?array
    {
        return $this->allAlignedPositions;
    }

    public function setAllAlignedPositions(?array $allAlignedPositions): self
    {
        $this->allAlignedPositions = $allAlignedPositions;

        return $this;
    }

    public function getWinner(): ?int
    {
        return $this->winner;
    }

    public function setWinner(?int $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }
}
