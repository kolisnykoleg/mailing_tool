<?php

namespace App\Controller;

use App\Entity\Pool;
use App\Repository\PoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PoolController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PoolRepository
     */
    private $poolRepository;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PoolRepository $poolRepository,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager;
        $this->poolRepository = $poolRepository;
        $this->validator = $validator;
    }

    /**
     * @Route("/pool/create", name="createPool")
     */
    public function create(Request $request): Response
    {
        $response = [
            'success' => true,
            'text' => 'Pool saved',
        ];

        $pool = new Pool();
        $pool->setName($request->get('name'));
        $pool->setColor($request->get('color'));
        $this->entityManager->persist($pool);

        $errors = $this->validator->validate($pool);
        if (count($errors)) {
            $response = [
                'success' => false,
                'text' => $errors->get(0)->getMessage(),
            ];
        } else {
            $this->entityManager->flush();
        }

        return $this->json($response);
    }

    /**
     * @Route("/pool/roll", name="listPools")
     */
    public function list(): Response
    {
        $pools = $this->poolRepository->findAll();
        $data = array_map(function ($pool) {
            $lastMailing = $pool->_getCampaigns()->last();
            return [
                'id' => $pool->getId(),
                'name' => $pool->getName(),
                'color' => $pool->getColor(),
                'address_count' => $pool->_getAddresses()->count(),
                'mailing_count' => $pool->_getCampaigns()->count(),
                'mailing_date' => $lastMailing ? $lastMailing->getDate() : '',
            ];
        }, $pools);
        return $this->json(['data' => $data]);
    }

    /**
     * @Route("/pool/get/{id}", name="findPool")
     */
    public function find(Pool $pool): Response
    {
        return $this->json($pool);
    }

    /**
     * @Route("/pool/update/{id}", name="updatePool")
     */
    public function update(Request $request, Pool $pool): Response
    {
        $response = [
            'success' => true,
            'text' => 'Pool updated',
        ];

        $pool->setName($request->get('name'));
        $pool->setColor($request->get('color'));

        $errors = $this->validator->validate($pool);
        if (count($errors)) {
            $response = [
                'success' => false,
                'text' => $errors->get(0)->getMessage(),
            ];
        } else {
            $this->entityManager->flush();
        }

        return $this->json($response);
    }
}
