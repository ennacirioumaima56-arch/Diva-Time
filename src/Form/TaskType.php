<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la tâche',
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => 'ex. Design Homepage']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control rounded-3', 'rows' => 3]
            ])
            ->add('deadline', DateTimeType::class, [
                'label' => 'Date Limite (Optionnelle)',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control rounded-3']
            ])
            ->add('estimatedHours', NumberType::class, [
                'label' => 'Durée Max',
                'required' => false,
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => 'Entrez la valeur']
            ])
            ->add('estimatedDurationUnit', ChoiceType::class, [
                'label' => 'Unité',
                'choices' => [
                    'Heures' => 'hours',
                    'Jours' => 'days',
                    'Mois' => 'months',
                ],
                'attr' => ['class' => 'form-select rounded-3']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'À Faire' => 'pending',
                    'En Cours' => 'in_progress',
                    'Terminé' => 'completed',
                ],
                'attr' => ['class' => 'form-select rounded-3']
            ])
            ->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => 'name',
                'query_builder' => function (\App\Repository\ProjectRepository $pr) {
                    return $pr->createQueryBuilder('p')
                        ->where('p.isActive = :active')
                        ->setParameter('active', true);
                },
                'attr' => ['class' => 'form-select rounded-3']
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Assigner à',
                'attr' => ['class' => 'form-select rounded-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
