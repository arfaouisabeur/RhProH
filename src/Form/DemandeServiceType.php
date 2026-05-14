<?php

namespace App\Form;

use App\Entity\DemandeService;
use App\Entity\TypeService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class DemandeServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EntityType::class, [
                'class' => TypeService::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir un type de service...',
                'label' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Choisissez une famille puis une catégorie de service.'),
                ],
                // Used by the UI to build the "famille -> catégorie" UX
                'choice_attr' => static function (?TypeService $t): array {
                    if (!$t) return [];
                    return [
                        'data-categorie' => (string) $t->getCategorie(),
                    ];
                },
                'attr' => [
                    'id' => 'js-type-service',
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'label'    => false,
                'attr'     => [
                    'placeholder' => 'Cliquez sur ✨ Générer avec l\'IA ou décrivez votre besoin...',
                    'rows'        => 4,
                    'id'          => 'service-description',
                ],
            ])
            ->add('priorite', null, [
                'required' => false,
                'label' => false,
            ])
            ->add('pdf_path', null, [
                'required' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeService::class,
        ]);
    }
}
