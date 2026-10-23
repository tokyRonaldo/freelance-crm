<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Contact;
use App\Enum\ActivityKind;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityController extends AbstractController
{
    #[Route('/activities', name: 'app_activity_index', methods: ['GET'])]
    public function index(Request $request, ActivityRepository $repository, PaginatorInterface $paginator): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $kind = ActivityKind::tryFrom((string) $request->query->get('type', ''));

        $pagination = $paginator->paginate(
            $repository->searchByOwner($this->getUser(), $search, $kind),
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('activity/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'kind' => $kind,
            'kinds' => ActivityKind::cases(),
        ]);
    }

    #[Route('/activities/new', name: 'app_activity_add', methods: ['GET'])]
    public function add(Request $request, ContactRepository $contacts): Response
    {
        $user = $this->getUser();
        $contact = null;
        $form = null;

        if ($id = $request->query->getInt('contact')) {
            $candidate = $contacts->find($id);

            if ($candidate && $candidate->getOwner() === $user) {
                $contact = $candidate;
                $activity = (new Activity())->setOccurredAt(new \DateTimeImmutable());
                $form = $this->createForm(ActivityType::class, $activity, [
                    'contact' => $contact,
                    'action' => $this->generateUrl('app_activity_new', ['id' => $contact->getId()]),
                ]);
            }
        }

        return $this->render('activity/add.html.twig', [
            'contacts' => $contacts->findBy(['owner' => $user], ['lastName' => 'ASC']),
            'contact' => $contact,
            'form' => $form,
        ]);
    }

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
            $this->addFlash('error', 'Échange non enregistré : vérifiez le titre et la date.');
        }

        return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
    }

    #[Route('/activities/{id}/delete', name: 'app_activity_delete', methods: ['POST'])]
    public function delete(Request $request, Activity $activity, EntityManagerInterface $em): Response
    {
        if ($activity->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete-activity' . $activity->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($activity);
            $em->flush();
            $this->addFlash('success', 'Échange supprimé.');
        }

        $back = $request->getPayload()->getString('_back');
        if (str_starts_with($back, '/') && !str_starts_with($back, '//') && !str_contains($back, '\\')) {
            return $this->redirect($back);
        }

        return $this->redirectToRoute('app_activity_index');
    }
}