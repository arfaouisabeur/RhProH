<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationEmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire'),
                    new Length(
                        min: 2,
                        max: 120,
                        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères'
                    ),
                ],
                'attr' => ['placeholder' => 'Votre prénom'],
            ])

            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire'),
                    new Length(
                        min: 2,
                        max: 120,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères'
                    ),
                ],
                'attr' => ['placeholder' => 'Votre nom'],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: "L'email est obligatoire"),
                    new Email(message: 'Veuillez entrer un email valide'),
                ],
                'attr' => ['placeholder' => 'votre@email.com'],
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
                'attr' => ['placeholder' => '+216 XX XXX XXX'],
            ])

            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'constraints' => [
                    new Length(
                        min: 3,
                        minMessage: 'Adresse trop courte (min {{ limit }} caractères)'
                    ),
                ],
                'attr' => ['placeholder' => 'Ville, Pays'],
            ])

            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Le matricule est obligatoire'),
                    new Length(
                        min: 3,
                        max: 60,
                        minMessage: 'Le matricule doit contenir au moins {{ limit }} caractères'
                    ),
                ],
                'attr' => ['placeholder' => 'EMP-0001'],
            ])

            ->add('position', TextType::class, [
                'label' => 'Poste',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Le poste est obligatoire'),
                    new Length(min: 2, max: 120),
                ],
                'attr' => ['placeholder' => 'Votre poste'],
            ])

            ->add('dateEmbauche', DateType::class, [
                'label' => "Date d'embauche",
                'mapped' => false,
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: "La date d'embauche est obligatoire"),
                ],
            ])

            ->add('avatar', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
                        mimeTypesMessage: 'Veuillez uploader une image valide (JPG, PNG ou GIF)'
                    ),
                ],
            ])

            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Le mot de passe est obligatoire'),
                    new Length(
                        min: 8,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                    ),
                    new Regex(
                        pattern: '/[A-Z]/',
                        message: 'Le mot de passe doit contenir au moins une majuscule'
                    ),
                    new Regex(
                        pattern: '/[a-z]/',
                        message: 'Le mot de passe doit contenir au moins une minuscule'
                    ),
                    new Regex(
                        pattern: '/[0-9]/',
                        message: 'Le mot de passe doit contenir au moins un chiffre'
                    ),
                ],
                'attr' => ['placeholder' => '••••••••••••'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,

            // Désactive la validation des contraintes Assert de l'entité
            'validation_groups' => false,
        ]);
    }
}
