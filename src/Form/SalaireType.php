<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Salaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Salaire>
 */
class SalaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder

            ->add('mois', ChoiceType::class, [
                'choices' => [
                    'Janvier' => 1,
                    'Février' => 2,
                    'Mars' => 3,
                    'Avril' => 4,
                    'Mai' => 5,
                    'Juin' => 6,
                    'Juillet' => 7,
                    'Août' => 8,
                    'Septembre' => 9,
                    'Octobre' => 10,
                    'Novembre' => 11,
                    'Décembre' => 12,
                ],
                'placeholder' => 'Choisir mois',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Mois obligatoire')
                ]
            ])

            ->add('annee', NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Année obligatoire'),
                    new Assert\Range(
                        min: 2000,
                        max: 2100,
                        notInRangeMessage: 'Année invalide'
                    )
                ]
            ])

            ->add('montant', NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Montant obligatoire'),
                    new Assert\Positive(message: 'Montant doit être positif')
                ]
            ])

            ->add('date_paiement', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Payé' => 'PAYE',
                    'En attente' => 'EN_ATTENTE'
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Statut obligatoire')
                ]
            ]);

        // 🔥 ONLY IN CREATE
        if (!$isEdit) {
            $builder->add('contract', EntityType::class, [
                'class' => Contract::class,
                'choice_label' => function ($contract) {
                    return $contract->getEmploye()->getUser()->getFullName()
                        . ' | ' . $contract->getType()
                        . ' | ' . $contract->getSalaireBase() . ' DT';
                },
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Contrat obligatoire')
                ]
            ]);
        }

        // 🔥 DATE TRANSFORMER (UNCHANGED)
        $builder->get('date_paiement')
            ->addModelTransformer(new CallbackTransformer(
                fn($value) => $value ? new \DateTime($value) : null,
                fn($value) => $value ? $value->format('Y-m-d') : null
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salaire::class,
            'is_edit' => false
        ]);
    }
}