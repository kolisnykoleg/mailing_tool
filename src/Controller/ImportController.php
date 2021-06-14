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
     * @Route("/import", name="import")
     */
    public function index(Request $request, GenderApiClient $genderApiClient): Response
    {
        $response = [
            'success' => true,
            'text' => 'Import complete',
        ];

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

        $errorCount = 0;
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

            try {
                $address = new Address();
                $address->setBlacklist(false);
                $address->setReaction($this->reactionRepository->find(1));
                foreach ($data as $key => $val) {
                    $address->{"set$key"}(trim($val));
                }
                if (!empty($pool)) {
                    $address->setPool($pool);
                }
                if ($genderAuto) {
                    $address->setGender($genderApiClient->getGender($address->getFirstName()));
                }
                $this->entityManager->persist($address);
                $this->entityManager->flush();
            } catch (\HttpRequestException $e) {
                return $this->json(
                    [
                        'success' => false,
                        'text' => $e->getMessage(),
                    ]
                );
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        if (!empty($poolLimit) && $poolType == -2) {
            $this->splitPool($fileName, $poolLimit);
        } elseif ($poolType == 0) {
            $this->autoPool($fileName);
        }

        if ($errorCount) {
            $response = [
                'success' => false,
                'text' => "Duplicate addresses: $errorCount",
            ];
        }

        return $this->json($response);
    }

    /**
     * @Route("/import/upload", name="uploadImportFile")
     */
    public function uploadFile(Request $request): Response
    {
        $importFile = $request->files->get('file');
        $fileName = $importFile->getClientOriginalName();
        $uploadsDir = $this->getParameter('uploads_dir');

        try {
            $importFile->move($uploadsDir, $fileName);
            $response = [
                'success' => true,
                'text' => "$uploadsDir/$fileName",
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'text' => $e->getMessage(),
            ];
        }

        return $this->json($response);
    }

    /**
     * @Route("/import/amount", name="countRowsImport")
     */
    public function countRows(Request $request): Response
    {
        $filePath = $request->get('fileName');

        try {
            $reader = IOFactory::createReaderForFile($filePath)
                ->setReadDataOnly(true)
                ->load($filePath);

            $amount = $reader->getActiveSheet()->getHighestRow() - 1;
            $response = [
                'success' => true,
                'text' => "$amount entries",
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'text' => $e->getMessage(),
            ];
        }

        return $this->json($response);
    }

    /**
     * @Route("/import/titles", name="getTitlesImport")
     */
    public function getTitles(Request $request): Response
    {
        $filePath = $request->get('fileName');

        $response = [
            'success' => true,
            'text' => [
                [
                    'id' => '',
                    'text' => '',
                    'selected' => true,
                    'disabled' => true,
                ]
            ],
        ];

        try {
            $reader = IOFactory::createReaderForFile($filePath)
                ->setReadDataOnly(true)
                ->load($filePath);

            $row = $reader->getActiveSheet()->getRowIterator()->current();
            $cellIterator = $row->getCellIterator();
            foreach ($cellIterator as $cell) {
                if (is_null($value = $cell->getValue())) {
                    continue;
                }
                $response['text'][] = [
                    'id' => $value,
                    'text' => $value,
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'text' => $e->getMessage(),
            ];
        }

        return $this->json($response);
    }

    /**
     * @Route("/import/gender_api_stats", name="genderApiStats")
     */
    public function genderApiStats(GenderApiClient $genderApiClient): Response
    {
        try {
            $response = [
                'success' => true,
                'text' => $genderApiClient->getStats(),
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'text' => $e->getMessage(),
            ];
        }

        return $this->json($response);
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
            $this->entityManager->flush();

            foreach ($locations as $key => &$addresses) {
                $address = array_shift($addresses);
                $address->setPool($pool);
                $this->entityManager->flush();
                if (empty($addresses)) {
                    unset($locations[$key]);
                }
            }
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
            $this->entityManager->flush();

            foreach ($addresses as $address) {
                $address->setPool($pool);
                $this->entityManager->flush();
            }

            $index++;
        }
    }
}
