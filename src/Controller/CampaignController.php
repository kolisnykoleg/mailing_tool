<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Campaign;
use App\Entity\Pool;
use App\Repository\AddressRepository;
use App\Repository\CampaignRepository;
use App\Repository\PoolRepository;
use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use h4cc\WKHTMLToPDF\WKHTMLToPDF;
use mikehaertl\wkhtmlto\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/campaign")
 */
class CampaignController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var PoolRepository
     */
    private $poolRepository;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CampaignRepository $campaignRepository,
        AddressRepository $addressRepository,
        PoolRepository $poolRepository,
        TemplateRepository $templateRepository
    ) {
        $this->entityManager = $entityManager;
        $this->campaignRepository = $campaignRepository;
        $this->addressRepository = $addressRepository;
        $this->poolRepository = $poolRepository;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @Route("/create", name="createCampaign")
     */
    public function create(Request $request): Response
    {
        $pool = $this->poolRepository->find($request->get('pool'));
        $template = $this->templateRepository->find($request->get('template'));

        $campaign = new Campaign();
        $campaign->setPool($pool);
        $campaign->setTemplate($template);
        $campaign->setDate($request->get('date'));

        if ($addressId = $request->get('address_id')) {
            $address = $this->addressRepository->find($addressId);
            $campaign->setAddress($address);
            $addressList[] = $address;
        } else {
            $addressList = $pool->_getAddresses()->filter(
                function (Address $address) {
                    return $address->getStatus();
                }
            )->toArray();
        }

        $html = '';
        $twigTemplate = $this->get('twig')->createTemplate($template->getContent());
        foreach ($addressList as $address) {
            $html .= $twigTemplate->render(
                [
                    'date' => $campaign->getDate(),
                    'customerData' => [
                        'title' => $address->getTitle(),
                        'firstname' => $address->getFirstName(),
                        'lastname' => $address->getLastName(),
                        'street' => $address->getStreet(),
                        'zip' => $address->getZip(),
                        'city' => $address->getCity(),
                        'var_1' => $address->getVar1(),
                        'var_2' => $address->getVar2(),
                        'var_3' => $address->getVar3(),
                        'var_4' => $address->getVar4(),
                        'var_5' => $address->getVar5(),
                        'company' => $address->getCompany(),
                        'salutation' => $this->getSalutation($address),
                        'gendertext' => $this->getGenderText($address),
                        'addressgender' => $this->getAddressGender($address),
                        'addresslastname' => $this->getAddressSalutation($address),
                        'addresssalutation' => $this->getAddressSalutationFull($address),
                    ],
                ]
            );
        }

        if (empty($html)) {
            throw new \Exception('There are no active addresses in the pool');
        }

        $pdf = new Pdf(
            [
                'binary' => WKHTMLToPDF::PATH,
            ]
        );
        $pdf->addPage($html);

        $fileName = uniqid(($pool ? $pool->getName() : "Address_$addressId") . ' ' . date('d_m_y') . '_') . '.pdf';
        $uploadsDir = $this->getParameter('uploads_dir');
        if (!$pdf->saveAs("$uploadsDir/$fileName")) {
            throw new \Exception($pdf->getError());
        }

        $campaign->setFile($fileName);

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $this->json(['text' => 'Mailing created']);
    }

    /**
     * @Route("/list", name="listCampaigns")
     */
    public function list(Request $request): Response
    {
        $campaigns = $this->campaignRepository->findAll();
        return $this->json($campaigns);
    }

    /**
     * @Route("/list/pool/{id}", name="listCampaignsForPool")
     */
    public function listForPool(Pool $pool): Response
    {
        $campaigns = $pool->_getCampaigns();
        return $this->json($campaigns);
    }

    /**
     * @Route("/list/address/{id}", name="listCampaingnsForAddress")
     */
    public function listForAddress(Address $address): Response
    {
        $addressCampaigns = $address->_getCampaigns()->toArray();
        $addressPoolCampaigns = $address->getPool()->_getCampaigns()->toArray();
        $campaigns = array_merge($addressCampaigns, $addressPoolCampaigns);
        return $this->json($campaigns);
    }

    private function getSalutation(Address $address): string
    {
        $salutation = 'Sehr geehrte Damen und Herren';
        if ($address->getLastName() && $address->getGender()) {
            $salutation = 'Sehr geehrte';
            if ($address->getGender() == 'm') {
                $salutation .= 'r Herr ';
            } else {
                $salutation .= ' Frau ';
            }
            $salutation .= join(' ', array_filter([$address->getTitle(), $address->getLastName()]));
        }
        return $salutation;
    }

    private function getAddressSalutation(Address $address): string
    {
        $gender = $this->getGenderText($address);
        return join(' ', array_filter([$gender, $address->getTitle(), $address->getLastName()]));
    }

    private function getAddressSalutationFull(Address $address): string
    {
        $gender = $this->getAddressGender($address);
        return join(
            ' ',
            array_filter(
                ['z. Hd.', $gender, $address->getTitle(), $address->getFirstName(), $address->getLastName()]
            )
        );
    }

    private function getGenderText(Address $address): string
    {
        return $address->getGender() == 'm' ? 'Herr' : 'Frau';
    }

    private function getAddressGender(Address $address): string
    {
        return $address->getGender() == 'm' ? 'Herrn' : 'Frau';
    }

    private function formatCompany(Address $address): string
    {
        $company = $address->getCompany();
        if (strlen($company) > 40) {
            $middle = strrpos(substr($company, 0, floor(strlen($company) / 2)), ' ') + 1;
            return substr($company, 0, $middle) . '<br>' . substr($company, $middle);
        }
        return $company;
    }
}
