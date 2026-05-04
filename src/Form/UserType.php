<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom Complet',
                'attr' => ['placeholder' => 'Entrez le nom du membre', 'class' => 'form-control rounded-3'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => ['placeholder' => 'email@exemple.com', 'class' => 'form-control rounded-3'],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Niveau d\'accès',
                'choices'  => [
                    'Membre de l\'équipe' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => false,
                'expanded' => false,
                'mapped' => false, // We'll handle this in the controller as it's an array field
                'attr' => ['class' => 'form-select rounded-3'],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => $options['is_new'],
                'attr' => ['placeholder' => 'Mot de passe', 'class' => 'form-control rounded-3'],
                'constraints' => $options['is_new'] ? [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length(['min' => 6, 'minMessage' => 'Your password should be at least {{ limit }} characters', 'max' => 4096]),
                ] : [],
            ])
            ->add('isVerified', null, [
                'label' => 'Compte Vérifié',
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'form-check form-switch ms-2 d-flex align-items-center gap-2'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => true,
        ]);
    }
}
