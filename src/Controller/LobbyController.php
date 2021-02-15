<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
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

        $encoders = [new JsonEncoder()];
        $normalizers = [new UidNormalizer(), new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);
        
        $response = new Response(
            $this->serializer->serialize($game, 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );

        return $response;
    }
}
