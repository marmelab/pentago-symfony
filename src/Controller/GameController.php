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
use Symfony\Component\HttpFoundation\Response;
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
            json_encode(["status" => $params["status"], "value" => $params["value"]])
        );

        // The Publisher service is an invokable object
        $publisher($update); // Publish to the mercure hub to notify listeners
    }

    /**
     * @Route("/games", name="game_create", methods={"POST"})
     */
    public function newGame(request $request): Response
    {

        $content = $request->toArray();
        if (!$content || !$content["playerId"]) {
            return new Response("Id is required", 400);
        }
        $playerId = $content["playerId"];

        $playerRepository = $this->getDoctrine()->getRepository(Player::class);
        $player = $playerRepository->find($playerId);

        
        $game = $this->gameService->initGame($player);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($game);
        $entityManager->flush();


        $response = new Response(
            $this->serializer->serialize($game, 'json'),
            Response::HTTP_CREATED,
            ['Content-type' => 'application/json']
        );
        return $response;
    }

    /**
     * @Route("/games/{id}/join", name="game_join", methods={"POST"})
     */
    public function joinGame(Request $request, string $id, PublisherInterface $publisher): Response
    {
        $content = $request->toArray();
        if (!$content || !$content["playerId"]) {
            return new Response("Id and playerId are required", 400);
        }
        $playerId = $content["playerId"];


        $entityManager = $this->getDoctrine()->getManager();
        $player = $entityManager->getRepository(Player::class)->find($playerId);
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new Response("Game not found", 404);
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
        $this->notify($publisher, $game->getId(), ["status" => "join", "value" => null]);

        $response = new Response(
            $this->serializer->serialize($game, 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );
        return $response;
    }

   /**
     * @Route("/games/{id}", name="game_view", methods={"GET"})
     */
    public function game(string $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return new Response("Game not found", 404);
        }

        $response = new Response(
            $this->serializer->serialize($game, 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );

        return $response;
    }

    /**
     * @Route("/games", name="games", methods={"GET"})
     */
    public function games(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $games = $entityManager->getRepository(Game::class)->findBy(
            ['status' => $this->gameService::GAME_WAITING_OPPONENT],
            ['created' => 'DESC']
        );
        
        $response = new Response(
            $this->serializer->serialize($games, 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );

        return $response;
    }

    /**
     * @Route("/games/{id}/addMarble", name="addMarble")
     * Add a marble to the board
     */

    public function addMarble(Request $request, string $id, PublisherInterface $publisher): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);

        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$playerHash || !$game) {
            return $this->redirectToRoute('newGame');
        }


        if ($game->getStatus() === $this->gameService::GAME_WAITING_OPPONENT ||
            $game->getCurrentPlayerHash() !== $playerHash
        ) {
            return $this->redirectToRoute('game', ["id" => $game->getId()]);
        }

        $requestPosition = $request->get('position');

        // Value are stored like "x-y".
        $position = explode('-', $requestPosition);

        // Using loop.index in twig make it start to 1 instead of 0.
        // We need to remove 1 to each positions.
        $position[0] -= 1;
        $position[1] -= 1;

        $game = $this->gameService->addMarbleIfPositionIsValid($game, $position);
        $entityManager->flush();
        $currentPlayerValue = $this->gameService->getPlayerValue($game, $playerHash);
        $this->notify(
            $publisher,
            $game->getId(),
            [
                "status" => $this->gameService::ADD_MARBLE_STATUS,
                "value" => ["position" => $requestPosition, "playerValue" => $currentPlayerValue]
            ]
        );

        return $this->redirectToRoute('game', ["id" => $game->getId()]);
    }

    /**
     * @Route("/games/{id}/rotateQuarter", name="rotateQuarter")
     * Add a marble to the board
     */

    public function rotateQuarter(Request $request, string $id, PublisherInterface $publisher): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);

        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$playerHash || !$game) {
            return $this->redirectToRoute('newGame');
        }


        if ($game->getStatus() === $this->gameService::GAME_WAITING_OPPONENT) {
            return $this->redirectToRoute('game', ["id" => $game->getId()]);
        }


        $rotationKey = $request->get('rotation-key');

        $game = $this->gameService->rotateQuarterBy90Degrees($game, $rotationKey);

        $entityManager->flush();

        $this->notify(
            $publisher,
            $game->getId(),
            [
                "status" => $this->gameService::ROTATE_QUARTER_STATUS,
                "value" => $rotationKey
            ]
        );

        return $this->redirectToRoute('game', ["id" => $game->getId()]);
    }
}
