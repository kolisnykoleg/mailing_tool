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

/**
 * @Route("/address")
 */
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
    ) {
        $this->addressRepository = $addressRepository;
        $this->poolRepository = $poolRepository;
        $this->reactionRepository = $reactionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/create", name="createAddress")
     */
    public function create(Request $request): Response
    {
        $address = new Address();
        $this->setData($request, $address);
        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return $this->json(['text' => 'Address saved']);
    }

    /**
     * @Route("/list", name="listAddresses")
     */
    public function list(Request $request): Response
    {
        $pools = $request->get('pools');
        $duplicates = $request->get('duplicates');

        $query = $this->addressRepository->createQueryBuilder('a');

        if ($pools) {
            $query
                ->join('a.pool', 'p')
                ->andWhere($query->expr()->in('p.id', $pools));
        }

        if ($duplicates) {
            $cols = join(', ', $duplicates);
            $wherePool = $pools ? 'WHERE pool_id IN (' . join(', ', $pools) . ')' : '';
            $on = join(
                ' AND ',
                array_map(
                    function ($column) {
                        return "a.$column = b.$column";
                    },
                    $duplicates
                )
            );

            $duplicateIds = $this->entityManager
                ->getConnection()
                ->executeQuery(
                    "SELECT a.id, a.pool_id FROM address a
                    JOIN (
                        SELECT $cols, COUNT(id) AS duplicates
                        FROM address b
                        $wherePool
                        GROUP BY $cols
                        HAVING duplicates > 1) b
                    ON $on
                    $wherePool"
                )
                ->fetchFirstColumn();

            if (!$duplicateIds) {
                return $this->json(['data' => []]);
            }

            $query->andWhere($query->expr()->in('a.id', $duplicateIds));
        } else {
            $query->andWhere('a.blacklist = 0');
        }

        $addresses = $query
            ->getQuery()
            ->getResult();

        return $this->json($addresses);
    }

    /**
     * @Route("/blacklist", name="blacklistAddresses")
     */
    public function blacklist(): Response
    {
        $blacklist = $this->addressRepository->findBy(
            [
                'blacklist' => true,
            ]
        );
        return $this->json($blacklist);
    }

    /**
     * @Route("/get/{id}", name="findAddress")
     */
    public function find(Address $address): Response
    {
        return $this->json($address);
    }

    /**
     * @Route("/update/{id}", name="updateAddress")
     */
    public function update(Request $request, Address $address): Response
    {
        $this->setData($request, $address);
        $this->entityManager->flush();

        return $this->json(['text' => 'Address updated']);
    }

    /**
     * @Route("/delete/{id}", name="deleteAddress")
     */
    public function delete(Address $address): Response
    {
        $this->entityManager->remove($address);
        $this->entityManager->flush();

        return $this->json(['text' => 'Address deleted']);
    }

    /**
     * @Route("/add-to-blacklist/{id}", name="addAddressToBlacklist")
     */
    public function addToBlacklist(Address $address): Response
    {
        $address->setBlacklist(true);
        $this->entityManager->flush();

        return $this->json(['text' => 'Address added to blacklist']);
    }

    private function setData(Request $request, Address &$address)
    {
        $address->setCompany($request->get('company'));
        $address->setStreet($request->get('street'));
        $address->setStreetFormat($request->get('street'));
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
        $address->setStatus((boolean)$request->get('status'));
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
