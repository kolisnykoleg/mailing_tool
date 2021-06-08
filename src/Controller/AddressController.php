<?php

namespace App\Controller;

use App\Entity\Address;
use App\Repository\AddressRepository;
use App\Repository\PoolRepository;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AddressController extends AbstractController
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var PoolRepository
     */
    private $poolRepository;

    /**
     * @var ReactionRepository
     */
    private $reactionRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        AddressRepository $addressRepository,
        PoolRepository $poolRepository,
        ReactionRepository $reactionRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->addressRepository = $addressRepository;
        $this->poolRepository = $poolRepository;
        $this->reactionRepository = $reactionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/address/create", name="createAddress")
     */
    public function create(Request $request): Response
    {
        $response = [
            'success' => true,
            'text' => 'Address saved',
        ];

        $address = new Address();
        $this->setData($request, $address);
        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return $this->json($response);
    }

    /**
     * @Route("/address/roll", name="listAddresses")
     */
    public function list(): Response
    {
        $addresses = $this->addressRepository->findAll();
        return $this->json(['data' => $addresses]);
    }

    /**
     * @Route("/address/blacklist", name="blacklistAddresses")
     */
    public function blacklist(): Response
    {
        $blacklist = $this->addressRepository->findBy([
            'blacklist' => true,
        ]);
        return $this->json(['data' => $blacklist]);
    }

    /**
     * @Route("/address/get/{id}", name="findAddress")
     */
    public function find(Address $address): Response
    {
        return $this->json($address);
    }

    /**
     * @Route("/address/update/{id}", name="updateAddress")
     */
    public function update(Request $request, Address $address): Response
    {
        $response = [
            'success' => true,
            'text' => 'Address updated',
        ];

        $this->setData($request, $address);
        $this->entityManager->flush();

        return $this->json($response);
    }

    /**
     * @Route("/address/delete/{id}", name="deleteAddress")
     */
    public function delete(Address $address): Response
    {
        $this->entityManager->remove($address);
        $this->entityManager->flush();
        return new Response();
    }

    /**
     * @Route("/address/add_to_blacklist/{id}", name="addAddressToBlacklist")
     */
    public function addToBlacklist(Address $address): Response
    {
        $address->setBlacklist(true);
        $this->entityManager->flush();
        return new Response();
    }

    private function setData(Request $request, Address &$address)
    {
        $address->setCompany($request->get('company'));
        $address->setStreet($request->get('street'));
        $address->setZip($request->get('zip'));
        $address->setCity($request->get('city'));
        $address->setCountry($request->get('country'));
        $address->setTitle($request->get('title'));
        $address->setFirstName($request->get('first_name'));
        $address->setLastName($request->get('last_name'));
        $address->setPosition($request->get('position'));
        $address->setPhone($request->get('phone'));
        $address->setEmail($request->get('email'));
        $address->setGender($request->get('gender'));
        $address->setStatus($request->get('status'));
        $address->setComment($request->get('comment'));
        $address->setFileUrl($request->get('file_url'));
        $address->setVar1($request->get('var_1'));
        $address->setVar2($request->get('var_2'));
        $address->setVar3($request->get('var_3'));
        $address->setVar4($request->get('var_4'));
        $address->setVar5($request->get('var_5'));
        $address->setPool($this->poolRepository->find($request->get('pool_id')));
        $address->setReaction($this->reactionRepository->find($request->get('reaction')));
        $address->setBlacklist(false);
    }
}
