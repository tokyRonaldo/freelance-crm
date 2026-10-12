<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Enum\ActivityKind;
use App\Repository\OpportunityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Contact $contact */
        $contact = $options['contact'];

        $builder
            ->add('type', EnumType::class, [
                'label' => "Type d'échange",
                'class' => ActivityKind::class,
                'choice_label' => fn (ActivityKind $k) => $k->label(),
            ])
            ->add('occurredAt', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('description', TextareaType::class, [
                'label' => "Compte-rendu (facultatif)",
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('opportunity', EntityType::class, [
                'label' => 'Opportunité liée (facultatif)',
                'class' => Opportunity::class,
                'required' => false,
                'placeholder' => 'Aucune',
                'choice_label' => 'title',
                'query_builder' => fn (OpportunityRepository $repo) => $repo->createQueryBuilder('o')
                    ->andWhere('o.contact = :contact')
                    ->andWhere('o.owner = :owner')
                    ->setParameter('contact', $contact)
                    ->setParameter('owner', $contact->getOwner())
                    ->orderBy('o.createdAt', 'DESC'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Activity::class]);
        $resolver->setRequired('contact');
        $resolver->setAllowedTypes('contact', Contact::class);
    }
}