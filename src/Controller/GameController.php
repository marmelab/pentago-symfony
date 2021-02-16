<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Entity\Game;
use App\Entity\Player;
use App\Service\GameService;
use App\Service\PlayerService;
use App\Service\MercureCookieService;

use App\Dto\GameDto;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'pentago';

    private GameService $gameService;

    public function __construct(GameService $gameService, PlayerService $playerService)
    {
        $this->gameService = $gameService;
        $this->playerService = $playerService;

        $encoders = [new JsonEncoder()];
        $normalizers = [new UidNormalizer(), new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function notify(PublisherInterface $publisher, UuidV4 $gameId, array $params)
    {
        // Front end side, we listen events from Mercure hub on DOMAIN/games/id.
        // Generate this URL :
        $url =  $this->generateUrl('game_view', [
            'id' => $gameId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Create an Update object
        $update = new Update(
            $url,
            json_encode($params)
        );

        // The Publisher service is an invokable object
        $publisher($update); // Publish to the mercure hub to notify listeners
    }

    /**
     * @Route("/games", name="game_create", methods={"POST"})
     */
    public function newGame(request $request): JsonResponse
    {

        $content = $request->toArray();
        if (!$content || !$content["playerId"]) {
            return new JsonResponse("Id is required", JsonResponse::HTTP_BAD_REQUEST);
        }
        $playerId = $content["playerId"];

        $playerRepository = $this->getDoctrine()->getRepository(Player::class);
        $player = $playerRepository->find($playerId);

        
        $game = $this->gameService->initGame($player);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($game);
        $entityManager->flush();


        $response = JsonResponse::fromJsonString(
            $this->serializer->serialize($game, 'json'),
            JsonResponse::HTTP_CREATED,
        );
        return $response;
    }

    /**
     * @Route("/games/{id}/join", name="game_join", methods={"POST"})
     */
    public function joinGame(Request $request, string $id, PublisherInterface $publisher): JsonResponse
    {
        $content = $request->toArray();
        if (!$content || !$content["playerId"]) {
            return new JsonResponse("Id and playerId are required", JsonResponse::HTTP_BAD_REQUEST);
        }
        $playerId = $content["playerId"];


        $entityManager = $this->getDoctrine()->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new JsonResponse("Game not found", JsonResponse::HTTP_NOT_FOUND);
        }

        // If game is not started yet, we waiting for players !
        if (!$this->gameService->isStarted($game)) {
            if ($game->getPlayer1() === null) {
                // If this game has no player 1
                // (this case is impossible in theory but for debugging purpose it's useful)
                // Set this player as player 1
                $game->setPlayer1($player);
            } elseif ($player !== $game->getPlayer1() &&
                $game->getPlayer2() === null
            ) {
                // If game has no player 2
                // Start this game !
                $game = $this->gameService->setPlayer2AndStartGame($player, $game);
            }
            $entityManager->flush();
        }
        $this->notify(
            $publisher,
            $game->getId(),
            ["status" => "join", "value" => null]
        );

        $response = JsonResponse::fromJsonString(
            $this->serializer->serialize($game, 'json'),
            JsonResponse::HTTP_OK
        );
        return $response;
    }

   /**
     * @Route("/games/{id}", name="game_view", methods={"GET"})
     */
    public function game(string $id): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new JsonResponse("Game not found", JsonResponse::HTTP_NOT_FOUND);
        }

        $response = JsonResponse::fromJsonString(
            $this->serializer->serialize($game, 'json'),
            JsonResponse::HTTP_OK,
        );

        return $response;
    }

    /**
     * @Route("/games", name="games", methods={"GET"})
     */
    public function games(): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        $games = $entityManager->getRepository(Game::class)->findBy(
            ['status' => $this->gameService::GAME_WAITING_OPPONENT],
            ['created' => 'DESC']
        );
        
        $response = JsonResponse::fromJsonString(
            $this->serializer->serialize($games, 'json'),
            JsonResponse::HTTP_OK,
        );

        return $response;
    }

    /**
     * @Route("/games/{id}/addMarble", name="addMarble", methods={"POST"})
     * Add a marble to the board
     */

    public function addMarble(Request $request, string $id, PublisherInterface $publisher): JsonResponse
    {
        $content = $request->toArray();
        if (!$content || !$content["playerId"] || !$content["position"]) {
            return new JsonResponse("playerId and marble are required", JsonResponse::HTTP_BAD_REQUEST);
        }
        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new JsonResponse("Game not found", JsonResponse::HTTP_NOT_FOUND);
        }

        $playerId = $content["playerId"];

        // $playerId is a string, we need to convert uuid to the same string.
        if ($playerId !== $game->getCurrentPlayer()->getId()->toRfc4122()) {
            return new JsonResponse("This is not your turn", JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($game->getTurnStatus() !== $this->gameService::ADD_MARBLE_STATUS) {
            return new JsonResponse("This is not the add_marble phase", JsonResponse::HTTP_BAD_REQUEST);
        }

        $position = $content["position"];

        $game = $this->gameService->addMarbleIfPositionIsValid($game, $position);
        $entityManager->flush();
        $currentPlayerValue = $this->gameService->getCurrentPlayerValue($game);

        $this->notify(
            $publisher,
            $game->getId(),
            [
                "status" => $this->gameService::ADD_MARBLE_STATUS,
                "value" => ["position" => $position, "playerValue" => $currentPlayerValue],
            ]
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($game, 'json'),
            JsonResponse::HTTP_OK,
        );
    }

    /**
     * @Route("/games/{id}/rotateQuarter", name="rotateQuarter", methods={"POST"})
     * Add a marble to the board
     */

    public function rotateQuarter(Request $request, string $id, PublisherInterface $publisher): JsonResponse
    {
        $content = $request->toArray();
        if (!$content || !$content["playerId"] || is_null($content["rotation"])) {
            return new JsonResponse("playerId and rotation are required", JsonResponse::HTTP_BAD_REQUEST);
        }
        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new JsonResponse("Game not found", JsonResponse::HTTP_NOT_FOUND);
        }

        $playerId = $content["playerId"];

        // $playerId is a string, we need to convert uuid to the same string.
        if ($playerId !== $game->getCurrentPlayer()->getId()->toRfc4122()) {
            return new JsonResponse("This is not your turn", JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($game->getTurnStatus() !== $this->gameService::ROTATE_QUARTER_STATUS) {
            return new JsonResponse("This is not the rotation phase", JsonResponse::HTTP_BAD_REQUEST);
        }

        $rotation = $content["rotation"];

        $game = $this->gameService->rotateQuarterBy90Degrees($game, $rotation);

        $entityManager->flush();

        $this->notify(
            $publisher,
            $game->getId(),
            [
                "status" => $this->gameService::ROTATE_QUARTER_STATUS,
                "value" => $rotation,
            ]
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($game, 'json'),
            JsonResponse::HTTP_OK,
        );
    }
}
