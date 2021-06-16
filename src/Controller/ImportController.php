<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Pool;
use App\Repository\AddressRepository;
use App\Repository\PoolRepository;
use App\Repository\ReactionRepository;
use App\Service\GenderApiClient;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/import")
 */
class ImportController extends AbstractController
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
     * @var ReactionRepository
     */
    private $reactionRepository;

    /**
     * @var AddressRepository
     */
    private $addressRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PoolRepository $poolRepository,
        ReactionRepository $reactionRepository,
        AddressRepository $addressRepository
    ) {
        $this->entityManager = $entityManager;
        $this->poolRepository = $poolRepository;
        $this->reactionRepository = $reactionRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * @Route("", name="import")
     */
    public function import(Request $request, GenderApiClient $genderApiClient): Response
    {
        $filePath = $request->get('file_name');
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $genderAuto = $request->get('gender_auto');

        $columnNames = [
            'Company' => $request->get('company_col'),
            'Street' => $request->get('street_col'),
            'Zip' => $request->get('zip_col'),
            'City' => $request->get('city_col'),
            'Country' => $request->get('country_col'),
            'FirstName' => $request->get('first_name_col'),
            'LastName' => $request->get('last_name_col'),
            'Title' => $request->get('title_col'),
            'Position' => $request->get('position_col'),
            'Phone' => $request->get('phone_col'),
            'Email' => $request->get('email_col'),
            'Gender' => $request->get('gender_col'),
            'Status' => $request->get('status_col'),
            'FileUrl' => $request->get('file_url_col'),
            'Var1' => $request->get('var_1_col'),
            'Var2' => $request->get('var_2_col'),
            'Var3' => $request->get('var_3_col'),
            'Var4' => $request->get('var_4_col'),
            'Var5' => $request->get('var_5_col'),
        ];

        $poolLimit = $request->get('pool_limit');
        $poolType = $request->get('import_pool_id');

        if ($poolType == -1) {
            $index = $this->poolRepository->getFreeIndex($fileName);
            $pool = new Pool();
            $pool->setName("$fileName $index");
            $this->entityManager->persist($pool);
            $this->entityManager->flush();
        }

        $reader = IOFactory::createReaderForFile($filePath)
            ->setReadDataOnly(true)
            ->load($filePath);

        $columnKeys = [];
        $cellIterator = $reader
            ->getActiveSheet()
            ->getRowIterator()
            ->current()
            ->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $key => $cell) {
            if (is_null($value = $cell->getValue())) {
                continue;
            }
            $columnName = array_search($value, $columnNames);
            if ($columnName !== false) {
                $columnKeys[$key] = $columnName;
            }
        }

        foreach ($reader->getActiveSheet()->getRowIterator(2) as $row) {
            $data = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $key => $cell) {
                if (is_null($value = $cell->getValue())) {
                    continue;
                }
                if (isset($columnKeys[$key])) {
                    $data[$columnKeys[$key]] = $value;
                }
            }

            $address = new Address();
            $address->setBlacklist(false);
            $address->setReaction($this->reactionRepository->find(1));
            foreach ($data as $key => $val) {
                $address->{"set$key"}(trim($val));
            }
            if ($street = $address->getStreet()) {
                $address->setStreetFormat($street);
            }
            if (!empty($pool)) {
                $address->setPool($pool);
            }
            if ($genderAuto) {
                $address->setGender($genderApiClient->getGender($address->getFirstName()));
            }
            $this->entityManager->persist($address);
        }
        $this->entityManager->flush();

        if (!empty($poolLimit) && $poolType == -2) {
            $this->splitPool($fileName, $poolLimit);
        } elseif ($poolType == 0) {
            $this->autoPool($fileName);
        }

        return $this->json(['text' => 'Import complete']);
    }

    /**
     * @Route("/upload", name="uploadImportFile")
     */
    public function uploadFile(Request $request): Response
    {
        $importFile = $request->files->get('file');
        $fileName = $importFile->getClientOriginalName();
        $uploadsDir = $this->getParameter('uploads_dir');
        $importFile->move($uploadsDir, $fileName);

        return $this->json(['text' => "$uploadsDir/$fileName"]);
    }

    /**
     * @Route("/amount", name="countRowsImport")
     */
    public function countRows(Request $request): Response
    {
        $filePath = $request->get('fileName');
        $reader = IOFactory::createReaderForFile($filePath)
            ->setReadDataOnly(true)
            ->load($filePath);

        $amount = $reader->getActiveSheet()->getHighestRow() - 1;

        return $this->json(['text' => "$amount entries"]);
    }

    /**
     * @Route("/titles", name="getTitlesImport")
     */
    public function getTitles(Request $request): Response
    {
        $filePath = $request->get('fileName');
        $reader = IOFactory::createReaderForFile($filePath)
            ->setReadDataOnly(true)
            ->load($filePath);

        $row = $reader->getActiveSheet()->getRowIterator()->current();
        $cellIterator = $row->getCellIterator();
        foreach ($cellIterator as $cell) {
            if (is_null($value = $cell->getValue())) {
                continue;
            }
            $titles[] = $value;
        }

        return $this->json($titles);
    }

    /**
     * @Route("/gender-api-stats", name="genderApiStats")
     */
    public function genderApiStats(GenderApiClient $genderApiClient): Response
    {
        return $this->json(['text' => $genderApiClient->getStats()]);
    }

    private function autoPool(string $name)
    {
        $addressList = $this->addressRepository->findBy(
            [
                'pool' => null,
            ]
        );

        $locations = [];
        foreach ($addressList as $address) {
            $key = join(
                ' ',
                array_filter(
                    [
                        $address->getCity(),
                        $address->getZip(),
                        $address->getStreet(),
                    ]
                )
            );
            $locations[$key][] = $address;
        }

        $index = $this->poolRepository->getFreeIndex($name);
        while (!empty($locations)) {
            $pool = new Pool();
            $pool->setName("$name $index");
            $this->entityManager->persist($pool);

            foreach ($locations as $key => &$addresses) {
                $address = array_shift($addresses);
                $address->setPool($pool);
                if (empty($addresses)) {
                    unset($locations[$key]);
                }
            }

            $this->entityManager->flush();
            $index++;
        }
    }

    private function splitPool(string $name, int $limit)
    {
        $addressList = $this->addressRepository->findBy(
            [
                'pool' => null,
            ]
        );

        $locations = [];
        foreach ($addressList as $address) {
            $key = join(
                ' ',
                array_filter(
                    [
                        $address->getCity(),
                        $address->getZip(),
                        $address->getStreet(),
                    ]
                )
            );
            $locations[$key][] = $address;
        }

        $pools = [];
        for ($i = 0; !empty($locations); $i++) {
            foreach ($locations as $key => &$addresses) {
                $address = array_shift($addresses);
                $pools[$i][] = $address;
                if (empty($addresses)) {
                    unset($locations[$key]);
                }
            }
        }

        $pools = array_reduce(
            $pools,
            function ($split, $pool) use ($limit) {
                return array_merge($split, array_chunk($pool, $limit));
            },
            []
        );

        $index = $this->poolRepository->getFreeIndex($name);
        foreach ($pools as $addresses) {
            $pool = new Pool();
            $pool->setName("$name $index");
            $this->entityManager->persist($pool);

            foreach ($addresses as $address) {
                $address->setPool($pool);
            }

            $this->entityManager->flush();
            $index++;
        }
    }
}
