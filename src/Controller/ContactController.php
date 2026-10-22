<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\TaskRepository;

#[Route('/contact')]
final class ContactController extends AbstractController
{
    #[Route('', name: 'app_contact_index', methods: ['GET'])]
    public function index(
        Request $request,
        ContactRepository $contactRepository,
        PaginatorInterface $paginator
    ): Response {
        $search = trim((string) $request->query->get('q', ''));

        $type = $request->query->get('type');
        $type = in_array($type, ['prospect', 'client'], true) ? $type : null;

        $pagination = $paginator->paginate(
            $contactRepository->searchByOwner($this->getUser(), $search, $type),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('contact/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'type' => $type,
        ]);
    }

    #[Route('/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact->setOwner($this->getUser());
            $contact->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($contact);
            $entityManager->flush();

            return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_show', methods: ['GET'])]
    public function show(Contact $contact, ActivityRepository $activityRepository, TaskRepository $taskRepository): Response
    {
        if ($contact->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $activity = (new Activity())->setOccurredAt(new \DateTimeImmutable());

        $form = $this->createForm(ActivityType::class, $activity, [
            'contact' => $contact,
            'action' => $this->generateUrl('app_activity_new', ['id' => $contact->getId()]),
        ]);

        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
            'activities' => $activityRepository->findBy(['contact' => $contact], ['occurredAt' => 'DESC']),
            'tasks' => $taskRepository->openForContact($contact),
            'activityForm' => $form,
        ]);
    }

    #[Route('/{id}/convert', name: 'app_contact_convert', methods: ['POST'])]
    public function convert(Request $request, Contact $contact, EntityManagerInterface $em): Response
    {
        if ($contact->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('convert' . $contact->getId(), $request->getPayload()->getString('_token'))) {
            $contact->setType('client');
            $em->flush();
            $this->addFlash('success', 'Ce contact est maintenant un client.');
        }

        return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($contact->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($contact->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$contact->getOpportunities()->isEmpty()) {
            $this->addFlash('error', 'Ce contact a encore des opportunités : traite-les avant de le supprimer.');

            return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
        }
        
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
    }
}
