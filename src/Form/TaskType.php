<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Repository\ContactRepository;
use App\Repository\OpportunityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false])
            ->add('contact', EntityType::class, [
                'label' => 'Contact associé',
                'class' => Contact::class,
                'required' => false,
                'placeholder' => 'Aucun',
                'choice_label' => fn (Contact $c) => trim($c->getFirstName() . ' ' . $c->getLastName())
                    . ($c->getCompany() ? ' — ' . $c->getCompany() : ''),
                'query_builder' => fn (ContactRepository $repo) => $repo->createQueryBuilder('c')
                    ->andWhere('c.owner = :owner')
                    ->setParameter('owner', $user)
                    ->orderBy('c.lastName', 'ASC'),
            ])
            ->add('opportunity', EntityType::class, [
                'label' => 'Opportunité associée',
                'class' => Opportunity::class,
                'required' => false,
                'placeholder' => 'Aucune',
                'choice_label' => 'title',
                'query_builder' => fn (OpportunityRepository $repo) => $repo->createQueryBuilder('o')
                    ->andWhere('o.owner = :owner')
                    ->setParameter('owner', $user)
                    ->orderBy('o.createdAt', 'DESC'),
            ])
            ->add('dueAt', DateType::class, [
                'label' => "Date d'échéance",
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('priority', EnumType::class, [
                'label' => 'Priorité',
                'class' => TaskPriority::class,
                'choice_label' => fn (TaskPriority $p) => $p->label(),
            ])
            ->add('status', EnumType::class, [
                'label' => 'Statut',
                'class' => TaskStatus::class,
                'choice_label' => fn (TaskStatus $s) => $s->label(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Task::class]);
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
    }
}