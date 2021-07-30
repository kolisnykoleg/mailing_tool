<?php

namespace App\Service;

use App\Entity\NameGender;
use App\Repository\NameGenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GenderApiClient
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var NameGenderRepository
     */
    private $nameGenderRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ContainerBagInterface $params,
        HttpClientInterface $client,
        EntityManagerInterface $entityManager,
        NameGenderRepository $nameGenderRepository
    ) {
        $this->key = $params->get('gender_api_key');
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->nameGenderRepository = $nameGenderRepository;
    }

    public function getStats(): array
    {
        $response = $this->client->request('GET', "https://gender-api.com/get-stats?key=$this->key");
        $data = $response->toArray();
        if (isset($data['errno'])) {
            throw new \Exception("Gender API: $data[errmsg]", $data['errno']);
        }
        return $data;
    }

    public function getGender(?string $firstName): ?string
    {
        if (!$firstName) {
            return null;
        }

        $name = strtolower($firstName);

        $nameGender = $this->nameGenderRepository->findOneBy(
            [
                'name' => $name,
            ]
        );

        if (!$nameGender) {
            $response = $this->client->request(
                'GET',
                'https://gender-api.com/get?' . http_build_query(
                    [
                        'name' => $name,
                        'country' => 'DE',
                        'key' => $this->key,
                    ]
                )
            );

            $data = $response->toArray();
            if (isset($data['errno'])) {
                throw new \Exception("Gender API: $data[errmsg]", $data['errno']);
            }

            $nameGender = new NameGender();
            $nameGender->setName($data['name']);
            $nameGender->setGender($data['gender']);
            $this->entityManager->persist($nameGender);
            $this->entityManager->flush();
        }

        return $nameGender->getGender();
    }
}