<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CampaignController extends AbstractController
{
    /**
     * @Route("/campaign/roll", name="campaigns")
     */
    public function list(): Response
    {
        return $this->json(['data' => []]);
    }
}
