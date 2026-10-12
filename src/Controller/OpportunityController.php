<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Form\OpportunityType;
use App\Repository\OpportunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/opportunity')]
final class OpportunityController extends AbstractController
{
    #[Route('', name: 'app_opportunity_index', methods: ['GET'])]
    public function index(OpportunityRepository $repository): Response
    {
        return $this->render('opportunity/index.html.twig', [
            'opportunities' => $repository->findBy(
                ['owner' => $this->getUser()],
                ['createdAt' => 'DESC']
            ),
        ]);
    }

    #[Route('/new', name: 'app_opportunity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $opportunity = new Opportunity();
        $form = $this->createForm(OpportunityType::class, $opportunity, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $opportunity->setOwner($this->getUser());
            $em->persist($opportunity);
            $em->flush();

            return $this->redirectToRoute('app_opportunity_index');
        }

        return $this->render('opportunity/new.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_opportunity_show', methods: ['GET'])]
    public function show(Opportunity $opportunity): Response
    {
        $this->denyUnlessOwner($opportunity);

        return $this->render('opportunity/show.html.twig', [
            'opportunity' => $opportunity,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_opportunity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Opportunity $opportunity, EntityManagerInterface $em): Response
    {
        $this->denyUnlessOwner($opportunity);

        $form = $this->createForm(OpportunityType::class, $opportunity, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_opportunity_index');
        }

        return $this->render('opportunity/edit.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_opportunity_delete', methods: ['POST'])]
    public function delete(Request $request, Opportunity $opportunity, EntityManagerInterface $em): Response
    {
        $this->denyUnlessOwner($opportunity);

        if ($this->isCsrfTokenValid('delete' . $opportunity->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($opportunity);
            $em->flush();
        }

        return $this->redirectToRoute('app_opportunity_index');
    }

    private function denyUnlessOwner(Opportunity $opportunity): void
    {
        if ($opportunity->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }
}