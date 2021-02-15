<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\GameService;
use App\Repository\GameRepository;

class LobbyController extends AbstractController
{

    public function __construct(GameService $gameService, GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
        $this->gameService = $gameService;
    }

    /**
     * @Route("/lobby", name="lobby")
     */
    public function index(): Response
    {
        $games = $this->gameRepository->findBy(
            ['status' => $this->gameService::GAME_WAITING_OPPONENT],
            ['created' => 'DESC']
        );

        return $this->render('lobby/index.html.twig', [
            'games' => $games,
        ]);
    }
}
