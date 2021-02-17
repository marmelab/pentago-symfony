<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
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
        $normalizers = [new ObjectNormalizer()];

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

        // casts the response JSON content to a PHP array
        $content = $advice->toArray();

         $response = JsonResponse::fromJsonString(
             $this->serializer->serialize($content, 'json'),
             JsonResponse::HTTP_OK
         );
        return $response;
    }
}
