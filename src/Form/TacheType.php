<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Prime;
use App\Entity\Projet;
use App\Entity\Tache;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEmploye = $options['is_employe'];

        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la tâche',
                'attr'  => ['placeholder' => 'Ex: Développer la page accueil'],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => true,
                'attr'     => ['rows' => 3, 'placeholder' => 'Détaillez la mission (min 10 caractères)...'],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'required' => true,
                'choices' => [
                    'À faire'  => 'a_faire',
                    'En cours' => 'en_cours',
                    'Terminée' => 'terminee',
                    'Bloquée'  => 'bloquee',
                ],
            ])
            ->add('level', ChoiceType::class, [
                'label'       => 'Priorité',
                'required'    => true,
                'choices'     => [
                    'Faible'  => 'faible',
                    'Moyenne' => 'moyenne',
                    'Haute'   => 'haute',
                    'Urgente' => 'urgente',
                ],
                'placeholder' => '-- Sélectionner --',
            ])
            ->add('date_debut', DateType::class, [
                'label'    => 'Date de début',
                'widget'   => 'single_text',
                'required' => true,
            ])
            ->add('date_fin', DateType::class, [
                'label'    => 'Date de fin',
                'widget'   => 'single_text',
                'required' => true,
            ])
        ;

        // Champs visibles uniquement pour le RH
        if (!$isEmploye) {
            $builder
                ->add('projet', EntityType::class, [
                    'class'        => Projet::class,
                    'choice_label' => fn(Projet $p) => $p->getTitre(),
                    'label'        => 'Projet',
                    'required'     => true,
                    'placeholder'  => '-- Sélectionner un projet --',
                ])
                ->add('employe', EntityType::class, [
                    'class'        => Employe::class,
                    'choice_label' => fn(Employe $e) => $e->getNom() . ' ' . $e->getPrenom(),
                    'label'        => 'Assigné à',
                    'required'     => true,
                    'placeholder'  => '-- Sélectionner un employé --',
                ])
                ->add('prime', EntityType::class, [
                    'class'        => Prime::class,
                    'choice_label' => fn(Prime $p) => $p->getId(),
                    'label'        => 'Prime associée',
                    'required'     => false,
                    'placeholder'  => '-- Aucune prime --',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
            'is_employe' => false,
            'projet'     => null,
        ]);
    }
}