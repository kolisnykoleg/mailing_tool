<?php

namespace App\Controller;

use App\Entity\Reaction;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("reaction")
 */
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
    ) {
        $this->reactionRepository = $reactionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/create", name="createReaction")
     */
    public function create(Request $request): Response
    {
        $reaction = new Reaction();
        $reaction->setName($request->get('text'));

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        return $this->json(['text' => 'Action saved']);
    }

    /**
     * @Route("/list", name="listReactions")
     */
    public function list(): Response
    {
        $reactions = $this->reactionRepository->findAll();
        return $this->json($reactions);
    }
}
