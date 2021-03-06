<?php

namespace App\Controller;

use App\Entity\Address;
use App\Repository\AddressRepository;
use App\Repository\PoolRepository;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/address")
 * @IsGranted("ROLE_USER")
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
     * @IsGranted("ROLE_ADMIN")
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

        if (!$duplicates) {
            if ($pools) {
                $query
                    ->join('a.pool', 'p')
                    ->andWhere($query->expr()->in('p.id', $pools));
            }
            $query->andWhere('a.blacklist = 0');
        }

        $addresses = $query
            ->getQuery()
            ->getResult();

        if ($duplicates) {
            $duplicateAddresses = [];
            $len = count($addresses);
            for ($i = 0; $i < $len; $i++) {
                for ($j = $i + 1; $j < $len; $j++) {
                    $isDuplicate = true;
                    foreach ($duplicates as $duplicate) {
                        if ($addresses[$i]->{"get$duplicate"}() != $addresses[$j]->{"get$duplicate"}()) {
                            $isDuplicate = false;
                            break;
                        }
                    }
                    if ($isDuplicate) {
                        if (!$pools) {
                            $duplicateAddresses[$addresses[$i]->getId()] = $addresses[$i];
                            $duplicateAddresses[$addresses[$j]->getId()] = $addresses[$j];
                        } elseif (
                            in_array($addresses[$i]->getPool()->getId(), $pools) ||
                            in_array($addresses[$j]->getPool()->getId(), $pools)
                        ) {
                            $duplicateAddresses[$addresses[$i]->getId()] = $addresses[$i];
                            $duplicateAddresses[$addresses[$j]->getId()] = $addresses[$j];
                        }
                    }
                }
            }
            $addresses = array_values($duplicateAddresses);
        }

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
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(Request $request, Address $address): Response
    {
        $this->setData($request, $address);
        $this->entityManager->flush();

        return $this->json(['text' => 'Address updated']);
    }

    /**
     * @Route("/delete/{id}", name="deleteAddress")
     * @IsGranted("ROLE_ADMIN")
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

    /**
     * @Route("/export", name="exportAddresses")
     * @IsGranted("ROLE_ADMIN")
     */
    public function export(Request $request): Response
    {
        $columns = [
            'Company',
            'Street',
            'Zip',
            'City',
            'Country',
            'Gender',
            'FirstName',
            'LastName',
            'Phone',
            'Email',
            'Comment',
            'Position',
            'Title',
            'FileUrl',
            'Var1',
            'Var2',
            'Var3',
            'Var4',
            'Var5',
        ];
        $ids = explode(',', $request->get('ids'));
        $addresses = $this->addressRepository->findBy(['id' => $ids]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $data[] = $columns;
        foreach ($addresses as $address) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $address->{"get$column"}();
            }
            $data[] = $row;
        }

        $sheet->fromArray($data);
        $writer = new Writer\Xlsx($spreadsheet);

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        ob_clean();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="export.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response->send();
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
