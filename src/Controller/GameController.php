<?php

namespace App\Controller;

use App\Service\BoardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'pentago';

    private BoardService $boardService;
    private SessionInterface $session;

    public function __construct(BoardService $boardService, SessionInterface $session)
    {
        $this->boardService = $boardService;
        $this->session = $session;
    }

    /**
     * @Route("/", name="newGame")
     */
    public function newGame(): Response
    {
        $board = $this->boardService->initBoard();
        $playerHash = $this->generatePlayerHash();

        $response = $this->redirectToRoute('game');

        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));
        $this->session->set($playerHash, $board);

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

        $board = $this->session->get($playerHash);

        return $this->render('game/index.html.twig', [
            'board' => $board,
            'action' => $this->generateUrl('addMarble'),
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

        $board = $this->session->get($playerHash);

        $position = $request->get('position');

        // Value are stored like "x-y".
        $position = explode('-', $position);

        // Using loop.index in twig make it start to 1 instead of 0.
        // We need to remove 1 to each positions.
        $position[0] -= 1;
        $position[1] -= 1;
        if ($this->boardService->isPositionAvailable($board, $position) === true) {
            $board = $this->boardService->addMarble($board, $position);
        }

        $this->session->set($playerHash, $board);

        return $this->redirectToRoute('game');
    }

    // Return a unique hash in order to simulate something like an authentication.
    protected function generatePlayerHash(): string
    {
        return hash('sha256', uniqid(), false);
    }
}
