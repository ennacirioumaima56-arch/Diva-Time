<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\TimeEntry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control rounded-3']
            ])
            ->add('hours', NumberType::class, [
                'label' => 'Hours Worked',
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => 'e.g. 7.5']
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note / Description',
                'required' => false,
                'attr' => ['class' => 'form-control rounded-3', 'rows' => 3, 'placeholder' => 'What did you work on?']
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'name',
                'label' => 'Assign to Project',
                'attr' => ['class' => 'form-select rounded-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeEntry::class,
        ]);
    }
}
