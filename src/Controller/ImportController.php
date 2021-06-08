<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{
    /**
     * @Route("/import/gender_api_stats", name="genderApiStats")
     */
    public function genderApiStats(): Response
    {
        return $this->json(['data' => []]);
    }
}
