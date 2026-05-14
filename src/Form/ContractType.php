<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Employe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Contract>
 */
class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['edit_mode'] ?? false;

        $builder

            ->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'input' => 'string',
                'input_format' => 'Y-m-d',
                'constraints' => [
                    new Assert\NotBlank(message: 'Start date is required')
                ]
            ])

            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'input' => 'string',
                'input_format' => 'Y-m-d',
                'constraints' => [
                    new Assert\NotBlank(message: 'End date is required')
                ]
            ])

            ->add('type', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Type is required'),
                    new Assert\Length(min: 3, minMessage: 'Type must be at least 3 characters')
                ]
            ])

            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Actif' => 'ACTIF',
                    'Terminé' => 'TERMINE',
                    'Suspendu' => 'SUSPENDU'
                ],
                'placeholder' => 'Choisir statut',
                'required' => true,
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Status is required')
                ]
            ])

            ->add('salaire_base', NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Salary is required'),
                    new Assert\Positive(message: 'Salary must be positive')
                ]
            ])

            ->add('description', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Description too long')
                ]
            ]);

        if (!$isEdit) {
            $builder->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => function ($emp) {
                    return $emp->getMatricule() . ' | ' . $emp->getUser()->getFullName();
                },
                'placeholder' => 'Choisir employé',
                'required' => true,
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Employee is required')
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
            'edit_mode' => false,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'contract_form'
        ]);
    }
}