<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $role = $options['role'] ?? null;

        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire'),
                    new Length(min: 2, max: 120),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire'),
                    new Length(min: 2, max: 120),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire'),
                    new Email(message: 'Veuillez entrer un email valide'),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'constraints' => [
                    new Regex(
                        pattern: '/^[0-9+\s]{8,}$/',
                        message: 'Veuillez entrer un numéro de téléphone valide'
                    ),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Candidat' => User::ROLE_CANDIDAT,
                    'Employé' => User::ROLE_EMPLOYE,
                    'RH' => User::ROLE_RH,
                ],
                'constraints' => [
                    new NotBlank(message: 'Le rôle est obligatoire'),
                ],
                'attr' => ['class' => 'form-control']
            ]);

        // Champs spécifiques pour Candidat
        if ($role === User::ROLE_CANDIDAT) {
            $builder
                ->add('niveauEtude', TextType::class, [
                    'label' => 'Niveau d\'études',
                    'required' => false,
                    'mapped' => false,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('experience', IntegerType::class, [
                    'label' => 'Années d\'expérience',
                    'required' => false,
                    'mapped' => false,
                    'attr' => ['class' => 'form-control', 'min' => 0]
                ]);
        }

        // Champs spécifiques pour Employé
        if ($role === User::ROLE_EMPLOYE) {
            $builder
                ->add('matricule', TextType::class, [
                    'label' => 'Matricule',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Le matricule est obligatoire'),
                        new Length(min: 3, max: 60),
                    ],
                    'attr' => ['class' => 'form-control']
                ])
                ->add('position', TextType::class, [
                    'label' => 'Poste',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Le poste est obligatoire'),
                        new Length(min: 2, max: 120),
                    ],
                    'attr' => ['class' => 'form-control']
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'role' => null,
        ]);
    }
}
