<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

use App\Entity\Game;
use App\Service\GameService;

class IAController extends AbstractController
{
    const COOKIE_KEY = 'pentago';

    private GameService $gameService;

    public function __construct(GameService $gameService, HttpClientInterface $client)
    {
        $this->gameService = $gameService;
        $this->client = $client;
        $encoders = [new JsonEncoder()];
        $normalizers = [new UidNormalizer(), new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * @Route("/games/{id}/advice", name="game_advice", methods={"GET"})
     */
    public function getAdvice(string $id): JsonResponse
    {
        
        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new JsonResponse("Game not found", JsonResponse::HTTP_NOT_FOUND);
        }

        $playerValue = $this->gameService->getCurrentPlayerValue($game);

        $advice = $this->client->request(
            'POST',
            $this->getParameter('app.ai_url'),
            [
                'json' => [
                    'board' => $game->getBoard(),
                    'currentPlayer' => $playerValue
                ]
            ]
        );
        $statusCode = $advice->getStatusCode();

        if ($statusCode > 399) {
            return new JsonResponse("Advice service not available", $statusCode);
        }
        // casts the response JSON content to a PHP array
        $content = $advice->toArray();

         $response = JsonResponse::fromJsonString(
             $this->serializer->serialize($content, 'json'),
             JsonResponse::HTTP_OK
         );
        return $response;
    }

    /**
     * @Route("/games/{id}/computer/play", name="game_computer_play", methods={"POST"})
     */
    public function getComputerPlay(string $id): JsonResponse
    {
        
        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new JsonResponse("Game not found", JsonResponse::HTTP_NOT_FOUND);
        }

        $advice = $this->client->request(
            'POST',
            $this->getParameter('app.ai_url'),
            [
                'json' => [
                    'board' => $game->getBoard(),
                    'currentPlayer' => 2
                ]
            ]
        );
        $statusCode = $advice->getStatusCode();

        if ($statusCode > 399) {
            return new JsonResponse("Advice service not available", $statusCode);
        }
        // casts the response JSON content to a PHP array
        $content = $advice->toArray();
        $position = $content["PlaceMarble"];
        $game = $this->gameService->addMarbleIfPositionIsValid($game, $position);

        $rotationKey = $this->gameService->getRotationKeyFromIA($content["Rotate"]);

        $game = $this->gameService->rotateQuarterBy90Degrees($game, $rotationKey);
        $game->setCurrentPlayer($game->getPlayer1());
        $entityManager->flush();

        $response = JsonResponse::fromJsonString(
            $this->serializer->serialize($game, 'json'),
            JsonResponse::HTTP_OK
        );
        return $response;
    }
}
