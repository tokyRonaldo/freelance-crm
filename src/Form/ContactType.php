<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Statut du contact',
                'choices' => ['Prospect' => 'prospect', 'Client' => 'client'],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('firstName', TextType::class, ['label' => 'Prénom', 'required' => false])
            ->add('company', TextType::class, ['label' => 'Entreprise', 'required' => false])
            ->add('source', ChoiceType::class, [
                'label' => 'Source du contact',
                'required' => false,
                'placeholder' => 'Non précisée',
                'choices' => [
                    'LinkedIn' => 'linkedin',
                    'Réseau personnel' => 'reseau',
                    'Site web' => 'site_web',
                    'Autre' => 'autre',
                ],
            ])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail', 'required' => false])
            ->add('phone', TelType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('website', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
                'default_protocol' => 'https',
            ])
            ->add('notes', TextareaType::class, ['label' => 'Notes rapides', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Contact::class]);
    }
}