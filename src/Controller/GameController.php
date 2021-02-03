<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Game;
use App\Service\GameService;
use App\Service\PlayerService;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'pentago';

    private GameService $gameService;

    public function __construct(GameService $gameService, PlayerService $playerService)
    {
        $this->gameService = $gameService;
        $this->playerService = $playerService;
    }

    /**
     * @Route("/game/create", name="newGame")
     */
    public function newGame(): Response
    {

        $entityManager = $this->getDoctrine()->getManager();
        $playerHash = $this->playerService->generatePlayerHash();
        $game = $this->gameService->initGame($playerHash);

        $entityManager->persist($game);
        $entityManager->flush();

        $response = $this->redirectToRoute(
            'game',
            [
                "id" => $game->getId()
            ]
        );
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));

        return $response;
    }

    /**
     * @Route("/games/{id}/join", name="joinGame")
     */
    public function joinGame(Request $request, string $id): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);

        $entityManager = $this->getDoctrine()->getManager();

        $game = $entityManager->getRepository(Game::class)->find($id);
        if (!$game) {
            return $this->redirectToRoute('newGame');
        }

        // If game is not started yet, we waiting for players !
        if (!$this->gameService->isStarted($game)) {
            if (!$playerHash) {
                $playerHash = $this->playerService->generatePlayerHash();
            }

            if ($game->getPlayer1Hash() === null) {
                // If this game has no player 1 (this case is impossible in theory but for debugging purpose it's useful)
                // Set this player as player 1
                $game->setPlayer1Hash($playerHash);
            } elseif ($playerHash !== $game->getPlayer1Hash() && $game->getPlayer2Hash() === null) {
                // If game has no player 2
                // Start this game !
                $game = $this->gameService->setPlayer2AndStartGame($playerHash, $game);
            }
            $entityManager->flush();
        }



        // Anyway, player or not, we should redirect to this game
        $response = $this->redirectToRoute(
            'game',
            [
                "id" => $game->getId()
            ]
        );
        // With the $playerHash as cookie !
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));
        return $response;
    }

    /**
     * @Route("/games/{id}", name="game")
     * Used to display the board
     */
    public function game(Request $request, string $id): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);

        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            return $this->redirectToRoute('newGame');
        }

        $action = $game->getTurnStatus() === GameService::ADD_MARBLE_STATUS ?
            $this->generateUrl('addMarble', ["id" => $game->getId()]) :
            $this->generateUrl('rotateQuarter', ["id" => $game->getId()]);

        // Now we need to get if this user is player1, 2 or if he doesn't play.
        $yourValue = $this->gameService->getPlayerValue($game, $playerHash);

        return $this->render('game/index.html.twig', [
            'board' => $game->getBoard(),
            'status' => $game->getStatus(),
            'winner' => $game->getWinner(),
            'allAlignedPositions' => $game->getAllAlignedPositions(),
            'turnStatus' => $game->getTurnStatus(),
            'isYourTurn' => $game->getCurrentPlayerHash() === $playerHash,
            'yourValue' => $yourValue,
            'isWitness' => !$yourValue,
            'action' =>  $action,
            'method' => 'POST',
        ]);
    }

    /**
     * @Route("/games/{id}/addMarble", name="addMarble")
     * Add a marble to the board
     */

    public function addMarble(Request $request, string $id): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);

        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$playerHash || !$game) {
            return $this->redirectToRoute('newGame');
        }


        if (
            $game->getStatus() === $this->gameService::GAME_WAITING_OPPONENT ||
            $game->getCurrentPlayerHash() !== $playerHash
        ) {
            return $this->redirectToRoute('game', ["id" => $game->getId()]);
        }

        $position = $request->get('position');

        // Value are stored like "x-y".
        $position = explode('-', $position);

        // Using loop.index in twig make it start to 1 instead of 0.
        // We need to remove 1 to each positions.
        $position[0] -= 1;
        $position[1] -= 1;

        $game = $this->gameService->addMarbleIfPositionIsValid($game, $position);
        $entityManager->flush();

        return $this->redirectToRoute('game', ["id" => $game->getId()]);
    }

    /**
     * @Route("/games/{id}/rotateQuarter", name="rotateQuarter")
     * Add a marble to the board
     */

    public function rotateQuarter(Request $request, string $id): Response
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

        return $this->redirectToRoute('game', ["id" => $game->getId()]);
    }
}
