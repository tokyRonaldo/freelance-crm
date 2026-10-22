<?php

namespace App\Controller;

use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Form\TaskType;
use App\Repository\ContactRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/task')]
final class TaskController extends AbstractController
{
    #[Route('', name: 'app_task_index', methods: ['GET'])]
    public function index(TaskRepository $repo): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        return $this->render('task/index.html.twig', [
            'overdue' => $repo->overdue($user, $today),
            'today' => $repo->dueToday($user, $today),
            'upcoming' => $repo->upcoming($user, $today),
            'done' => $repo->recentlyDone($user),
        ]);
    }

    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ContactRepository $contacts): Response
    {
        $task = (new Task())->setDueAt(new \DateTimeImmutable('tomorrow'));

        // Venant d'une fiche contact : /task/new?contact=12 pré-remplit le contact.
        $fromContact = null;
        if ($id = $request->query->getInt('contact')) {
            $candidate = $contacts->find($id);
            if ($candidate && $candidate->getOwner() === $this->getUser()) {
                $fromContact = $candidate;
                $task->setContact($candidate);
            }
        }

        $form = $this->createForm(TaskType::class, $task, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setOwner($this->getUser());
            $em->persist($task);
            $em->flush();
            $this->addFlash('success', 'Tâche enregistrée.');

            return $fromContact
                ? $this->redirectToRoute('app_contact_show', ['id' => $fromContact->getId()])
                : $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form,
            'cancel_url' => $fromContact
                ? $this->generateUrl('app_contact_show', ['id' => $fromContact->getId()])
                : $this->generateUrl('app_task_index'),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $this->denyUnlessOwner($task);

        $form = $this->createForm(TaskType::class, $task, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Tâche mise à jour.');

            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/edit.html.twig', ['task' => $task, 'form' => $form]);
    }

    #[Route('/{id}/toggle', name: 'app_task_toggle', methods: ['POST'])]
    public function toggle(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $this->denyUnlessOwner($task);

        if ($this->isCsrfTokenValid('toggle' . $task->getId(), $request->getPayload()->getString('_token'))) {
            $task->setStatus($task->getStatus() === TaskStatus::Done ? TaskStatus::Todo : TaskStatus::Done);
            $em->flush();
        }

        return $this->redirectBack($request);
    }

    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $this->denyUnlessOwner($task);

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($task);
            $em->flush();
            $this->addFlash('success', 'Tâche supprimée.');
        }

        return $this->redirectBack($request);
    }

    private function denyUnlessOwner(Task $task): void
    {
        if ($task->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }

    /** Retourne à la page d'origine (liste ou fiche contact), en refusant tout lien externe. */
    private function redirectBack(Request $request): Response
    {
        $back = $request->getPayload()->getString('_back');

        if (str_starts_with($back, '/') && !str_starts_with($back, '//') && !str_contains($back, '\\')) {
            return $this->redirect($back);
        }

        return $this->redirectToRoute('app_task_index');
    }
}