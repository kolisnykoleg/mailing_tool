<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/template")
 */
class TemplateController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TemplateRepository $templateRepository
    ) {
        $this->entityManager = $entityManager;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @Route("/save", name="createTemplate")
     */
    public function create(Request $request): Response
    {
        $template = new Template();
        $this->setData($request, $template);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'text' => 'Template saved',
            ]
        );
    }

    /**
     * @Route("/roll", name="listTemplates")
     */
    public function list(): Response
    {
        $templates = $this->templateRepository->findAll();
        return $this->json(['data' => $templates]);
    }

    /**
     * @Route("/get/{id}", name="findTemplate")
     */
    public function find(Template $template): Response
    {
        return $this->json($template);
    }

    /**
     * @Route("/update/{id}", name="updateTemplate")
     */
    public function update(Request $request, Template $template): Response
    {
        $this->setData($request, $template);
        $this->entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'text' => 'Template updated',
            ]
        );
    }

    /**
     * @Route("/preview")
     */
    public function preview(Request $request): Response
    {
        return new Response($request->get('file'));
    }

    private function setData(Request $request, Template &$template)
    {
        $template->setName($request->get('name'));
        $template->setSection($request->get('section'));
        $template->setContent($request->get('file'));
    }
}
