<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Contact;
use App\Form\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityController extends AbstractController
{
    #[Route('/contact/{id}/activities', name: 'app_activity_new', methods: ['POST'])]
    public function new(Request $request, Contact $contact, EntityManagerInterface $em): Response
    {
        if ($contact->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity, ['contact' => $contact]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setOwner($this->getUser());
            $activity->setContact($contact);
            $em->persist($activity);
            $em->flush();
            $this->addFlash('success', 'Échange enregistré.');
        } else {
            $this->addFlash('error', "Échange non enregistré : vérifiez le titre et la date.");
        }

        return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
    }
}