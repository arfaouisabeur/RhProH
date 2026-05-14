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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\File;

class RegistrationCandidatType extends AbstractType
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
                'attr' => ['placeholder' => 'Votre prénom', 'class' => 'form-control']
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
                'attr' => ['placeholder' => 'Votre nom', 'class' => 'form-control']
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire'),
                    new Email(message: 'Veuillez entrer un email valide'),
                ],
                'attr' => ['placeholder' => 'votre@email.com', 'class' => 'form-control']
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
                'attr' => ['placeholder' => '+216 XX XXX XXX', 'class' => 'form-control']
            ])

            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre adresse',
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])

            ->add('niveauEtude', TextType::class, [
                'label' => 'Niveau d\'études',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Ex: Licence, Master, Bac+5...',
                    'class' => 'form-control'
                ]
            ])

            ->add('experience', IntegerType::class, [
                'label' => 'Années d\'expérience',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Nombre d\'années',
                    'class' => 'form-control',
                    'min' => 0
                ]
            ])

            ->add('avatar', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        mimeTypesMessage: 'Veuillez uploader une image valide (JPG, PNG ou GIF)'
                    )
                ],
                'attr' => ['class' => 'form-control']
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
                'attr' => [
                    'placeholder' => 'Mot de passe sécurisé',
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
