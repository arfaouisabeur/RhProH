<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Projet;
use App\Entity\RH;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du projet',
                'attr'  => ['placeholder' => 'Ex: Refonte du site web'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['rows' => 4],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'To Do'     => 'en_attente',
                    'Doing'     => 'en_cours',
                    'Done'      => 'termine',
                    'Cancelled' => 'annule',
                ],
            ])
            ->add('date_debut', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('date_fin', DateType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('rh', EntityType::class, [
                'class'        => RH::class,
                'choice_label' => fn(RH $rh) => $rh->getUser()?->getNom() . ' ' . $rh->getUser()?->getPrenom(),
                'label'        => 'Responsable RH',
                'required'     => false,
                'placeholder'  => '-- Sélectionner un RH --',
            ])
            ->add('responsable_employe', EntityType::class, [
                'class'        => Employe::class,
                'choice_label' => fn(Employe $e) => $e->getUser()?->getNom() . ' ' . $e->getUser()?->getPrenom(),
                'label'        => 'Employé responsable',
                'required'     => false,
                'placeholder'  => '-- Sélectionner un employé --',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Projet::class,
        ]);
    }
}