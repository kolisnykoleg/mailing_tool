<?php

namespace App\Controller;

use App\Entity\Reaction;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ReactionController extends AbstractController
{
    /**
     * @var ReactionRepository
     */
    private $reactionRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ReactionRepository $reactionRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->reactionRepository = $reactionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/reaction/create", name="createReaction")
     */
    public function create(Request $request): Response
    {
        $response = [
            'success' => true,
            'text' => 'Action saved',
        ];

        $reaction = new Reaction();
        $reaction->setName($request->get('text'));

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        return $this->json($response);
    }

    /**
     * @Route("/reaction/roll", name="listReactions")
     */
    public function list(): Response
    {
        $reactions = $this->reactionRepository->findAll();
        return $this->json(['data' => $reactions], 200, [], [
            AbstractNormalizer::IGNORED_ATTRIBUTES => [
                'addresses',
            ],
        ]);
    }
}
