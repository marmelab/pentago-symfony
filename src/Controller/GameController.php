<?php

namespace App\Controller;

use App\Service\GameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'pentago';

    private GameService $gameService;
    private SessionInterface $session;

    public function __construct(GameService $gameService, SessionInterface $session)
    {
        $this->gameService = $gameService;
        $this->session = $session;
    }

    /**
     * @Route("/", name="newGame")
     */
    public function newGame(): Response
    {
        $game = $this->gameService->initGame();

        $playerHash = $this->generatePlayerHash();

        $response = $this->redirectToRoute('game');

        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));
        $this->session->set($playerHash, $game);

        return $response;
    }

    /**
     * @Route("/game", name="game")
     * Used to display the board
     */
    public function game(Request $request): Response
    {

        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash || !$this->session->get($playerHash)) {
            return $this->redirectToRoute('newGame');
        }

        $game = $this->session->get($playerHash);

        $action = $game["turn_status"] === GameService::ADD_MARBLE_STATUS ?
            $this->generateUrl('addMarble') :
            $this->generateUrl('rotateQuarter');

        return $this->render('game/index.html.twig', [
            'board' => $game["board"],
            'turn_status' => $game["turn_status"],
            'action' =>  $action,
            'method' => 'POST',
        ]);
    }

    /**
     * @Route("/game/addMarble", name="addMarble")
     * Add a marble to the board
     */

    public function addMarble(Request $request): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash || !$this->session->get($playerHash)) {
            return $this->redirectToRoute('index');
        }

        $game = $this->session->get($playerHash);

        $position = $request->get('position');

        // Value are stored like "x-y".
        $position = explode('-', $position);

        // Using loop.index in twig make it start to 1 instead of 0.
        // We need to remove 1 to each positions.
        $position[0] -= 1;
        $position[1] -= 1;

        $game = $this->gameService->addMarbleIfPositionIsValid($game, $position);

        $this->session->set($playerHash, $game);

        return $this->redirectToRoute('game');
    }

    /**
     * @Route("/game/rotateQuarter", name="rotateQuarter")
     * Add a marble to the board
     */

    public function rotateQuarter(Request $request): Response
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash || !$this->session->get($playerHash)) {
            return $this->redirectToRoute('index');
        }

        $game = $this->session->get($playerHash);

        $rotationKey = $request->get('rotation-key');

        $game = $this->gameService->rotateQuarterBy90Degrees($game, $rotationKey);

        $this->session->set($playerHash, $game);
        return $this->redirectToRoute('game');
    }



    // Return a unique hash in order to simulate something like an authentication.
    protected function generatePlayerHash(): string
    {
        return hash('sha256', uniqid(), false);
    }
}
