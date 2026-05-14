<?php

namespace App\Form;

use App\Entity\CongeTt;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CongeTtType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeConge', ChoiceType::class, [
                'label'       => false,
                'placeholder' => 'Choisir un type...',
                'choices'     => [
                    'Congé annuel'       => 'Congé annuel',
                    'Congé maladie'      => 'Congé maladie',
                    'Congé maternité'    => 'Congé maternité',
                    'Congé paternité'    => 'Congé paternité',
                    'Congé professionnel'=> 'Congé professionnel',
                    'Congé exceptionnel' => 'Congé exceptionnel',
                    'Congé sans solde'   => 'Congé sans solde',
                    'RTT'                => 'RTT',
                ],
                'attr' => ['class' => 'cg-select'],
            ])
            ->add('dateDebut', DateType::class, [
                'label'  => false,
                'widget' => 'single_text',
                'html5'  => true,
                'input'  => 'datetime_immutable',
                'attr'   => ['class' => 'cg-date'],
            ])
            ->add('dateFin', DateType::class, [
                'label'  => false,
                'widget' => 'single_text',
                'html5'  => true,
                'input'  => 'datetime_immutable',
                'attr'   => ['class' => 'cg-date'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => false,
                'required' => true,
                'attr'     => [
                    'class'       => 'cg-textarea',
                    'placeholder' => 'Cliquez sur ✨ Générer avec l\'IA ou écrivez votre motif...',
                    'rows'        => 4,
                    'id'          => 'conge-description',
                ],
            ])
            ->add('certificatMedical', FileType::class, [
                'label'    => false,
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'accept' => '.jpg,.jpeg,.png,.pdf',
                    'id'     => 'certif-file-input',
                ],
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'application/pdf',
                        ],
                        mimeTypesMessage: 'Veuillez uploader un fichier JPG, PNG ou PDF.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => CongeTt::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'conge_tt_item',
        ]);
    }
}