<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Prime;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Prime>
 */
class PrimeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('montant', NumberType::class, [
                'attr' => [
                    'placeholder' => 'Montant en DT',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Montant obligatoire'),
                    new Assert\Positive(message: 'Montant doit être positif')
                ]
            ])

            ->add('date_attribution', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Date obligatoire')
                ]
            ])

            ->add('description', null, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description (optionnel)',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\Length(max: 255)
                ]
            ])

            ->add('contract', EntityType::class, [
                'class' => Contract::class,

                // 🔥 ADD PLACEHOLDER TO PREVENT AUTO-SELECTION
                'placeholder' => '-- Sélectionner un employé --',

                // 🔥 DISPLAY TEXT
                'choice_label' => function ($contract) {
                    return $contract->getEmploye()->getUser()->getFullName()
                        . ' | ' . $contract->getType()
                        . ' | ' . $contract->getSalaireBase() . ' DT';
                },

                // 🔥 🔥 THIS IS THE MAGIC 🔥 🔥
                'choice_attr' => function ($contract) {
                    return [
'data-employe' => $contract->getEmploye()->getUserId()                    ];
                },

                'attr' => ['class' => 'd-none'],

                'constraints' => [
                    new Assert\NotNull(message: 'Contrat obligatoire')
                ]
            ]);

        // 🔥 DATE TRANSFORMER
        $builder->get('date_attribution')
            ->addModelTransformer(new CallbackTransformer(
                function ($value) {
                    return $value ? new \DateTime($value) : null;
                },
                function ($value) {
                    return $value ? $value->format('Y-m-d') : null;
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prime::class,
        ]);
    }
}
