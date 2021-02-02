<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Game;
use App\Service\GameService;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'pentago';

    private GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    /**
     * @Route("/", name="newGame")
     */
    public function newGame(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $game = $this->gameService->initGame();

        $playerHash = $this->generatePlayerHash();

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
     * @Route("/game/{id}", name="game")
     * Used to display the board
     */
    public function game(Request $request, string $id): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);

        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager->getRepository(Game::class)->find($id);



        if (!$playerHash || !$game) {
            return $this->redirectToRoute('newGame');
        }


        $action = $game->getTurnStatus() === GameService::ADD_MARBLE_STATUS ?
            $this->generateUrl('addMarble', ["id" => $game->getId()]) :
            $this->generateUrl('rotateQuarter', ["id" => $game->getId()]);

        return $this->render('game/index.html.twig', [
            'board' => $game->getBoard(),
            'turnStatus' => $game->getTurnStatus(),
            'playerTurn' => $game->getPlayerTurn(),
            'action' =>  $action,
            'method' => 'POST',
        ]);
    }

    /**
     * @Route("/game/{id}/addMarble", name="addMarble")
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

        $position = $request->get('position');

        // Value are stored like "x-y".
        $position = explode('-', $position);

        // Using loop.index in twig make it start to 1 instead of 0.
        // We need to remove 1 to each positions.
        $position[0] -= 1;
        $position[1] -= 1;

        $game = $this->gameService->addMarbleIfPositionIsValid($game, $position, $game->getPlayerTurn());
        $entityManager->flush();

        return $this->redirectToRoute('game', ["id" => $game->getId()]);
    }

    /**
     * @Route("/game/{id}/rotateQuarter", name="rotateQuarter")
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

        $rotationKey = $request->get('rotation-key');

        $game = $this->gameService->rotateQuarterBy90Degrees($game, $rotationKey);

        $entityManager->flush();

        return $this->redirectToRoute('game', ["id" => $game->getId()]);
    }



    // Return a unique hash in order to simulate something like an authentication.
    protected function generatePlayerHash(): string
    {
        return hash('sha256', uniqid(), false);
    }
}
