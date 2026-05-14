<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\Evenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => "Titre de l'activité",
                'constraints' => [
                    new NotBlank(message: 'Le titre est obligatoire.'),
                    new Length(min: 3, max: 150,
                        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le titre ne peut dépasser {{ limit }} caractères.'
                    ),
                ],
                'attr' => ['placeholder' => 'Ex : Atelier cohésion'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'La description ne peut dépasser {{ limit }} caractères.'),
                ],
                'attr' => ['rows' => 3, 'placeholder' => 'Description optionnelle…'],
            ])
        ;

        if ($options['show_evenement_field']) {
            $builder->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'titre',
                'label' => 'Événement associé',
                // ✅ FIX : pas de NotBlank ici, on gère ça avec 'required' => true
                'required' => true,
                'placeholder' => '-- Choisir un événement --',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,
            'show_evenement_field' => true,
        ]);
    }
}