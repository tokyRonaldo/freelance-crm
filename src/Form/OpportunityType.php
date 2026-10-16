<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Entity\User;
use App\Enum\OpportunityStatus;
use App\Repository\ContactRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpportunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('title', TextType::class, ['label' => 'Titre du projet'])
            ->add('contact', EntityType::class, [
                'label' => 'Contact associé',
                'class' => Contact::class,
                'placeholder' => 'Sélectionner un contact',
                'choice_label' => fn (Contact $c) => trim($c->getFirstName() . ' ' . $c->getLastName())
                    . ($c->getCompany() ? ' — ' . $c->getCompany() : ''),
                'query_builder' => fn (ContactRepository $repo) => $repo->createQueryBuilder('c')
                    ->andWhere('c.owner = :owner')
                    ->setParameter('owner', $user)
                    ->orderBy('c.lastName', 'ASC'),
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant estimé (€ HT)',
                'scale' => 2,
                'input' => 'string',
            ])
            ->add('status', EnumType::class, [
                'label' => 'Statut',
                'class' => OpportunityStatus::class,
                'choice_label' => fn (OpportunityStatus $s) => $s->label(),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du besoin',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Opportunity::class]);
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
    }
}