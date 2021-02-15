<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Player;
use App\Dto\PlayerDto;

use App\Repository\GameRepository;

class PlayerController extends AbstractController
{
    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function loginOrCreatePlayer(Request $request)
    {
        $content = $request->toArray();
        if (!$content || !$content["name"]) {
            return new JsonResponse("Name is required", 400);
        }

        $repository = $this->getDoctrine()->getRepository(Player::class);
        $player = $repository->findOneBy([
            'name' => $content["name"],
        ]);
            
        if (!$player) {
            $player = new Player();
            $player->setName($content["name"]);

            $entityManager = $this->getDoctrine()->getManager();
    
            $entityManager->persist($player);
            $entityManager->flush();
        }
        

        $response = new JsonResponse(new PlayerDto($player));
        return $response;
    }
}
