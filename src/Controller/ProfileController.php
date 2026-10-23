<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $profileForm = $this->createForm(ProfileType::class, $user);
        $passwordForm = $this->createForm(ChangePasswordType::class);

        $profileForm->handleRequest($request);
        if ($profileForm->isSubmitted()) {
            if ($profileForm->isValid()) {
                $em->flush();
                $this->addFlash('success', 'Profil mis à jour.');

                return $this->redirectToRoute('app_profile');
            }

            // Saisie refusée : on remet l'utilisateur connecté dans son état d'origine (voir plus bas).
            $em->refresh($user);
        }

        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $passwordForm->get('newPassword')->getData()));
            $em->flush();
            $this->addFlash('success', 'Mot de passe modifié.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
        ]);
    }
}