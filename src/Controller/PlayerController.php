<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Entity\Player;

use App\Repository\GameRepository;

class PlayerController extends AbstractController
{
    public function __construct()
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new UidNormalizer(), new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);
    }
    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function loginOrCreatePlayer(Request $request)
    {
        $content = $request->toArray();
        if (!$content || !$content["name"]) {
            return new Response("Name is required", 400);
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
        
        $response = new Response(
            $this->serializer->serialize($player, 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );
        return $response;
    }
}
