<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Evenement;
use App\Entity\EventParticipation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Choice;

class EventParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_inscription', TextType::class, [
                'label'       => "Date d'inscription",
                'required'    => false,
                'empty_data'  => '',
                'constraints' => [
                    new NotBlank(message: "La date d'inscription est obligatoire."),
                    new Regex(
                        pattern: '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/',
                        message: "Format invalide : utilisez AAAA-MM-JJ (ex: 2025-06-15)."
                    ),
                ],
                'attr' => [
                    'placeholder'  => 'AAAA-MM-JJ',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label'       => 'Statut',
                'choices'     => [
                    'En attente' => 'en_attente',
                    'Accepté'    => 'accepte',
                    'Refusé'     => 'refuse',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir un statut.'),
                    new Choice(
                        choices: ['en_attente', 'accepte', 'refuse'],
                        message: 'Statut invalide.'
                    ),
                ],
                'placeholder' => '— Choisir un statut —',
            ])
            ->add('evenement', EntityType::class, [
                'class'        => Evenement::class,
                'choice_label' => 'titre',
                'label'        => 'Événement',
                'placeholder'  => '— Sélectionner un événement —',
                'constraints'  => [
                    new NotBlank(message: 'Veuillez sélectionner un événement.'),
                ],
            ])
            ->add('employe', EntityType::class, [
                'class'        => Employe::class,
                'choice_label' => function (Employe $e): string {
                    $user = $e->getUser();
                    return $user
                        ? $user->getPrenom() . ' ' . $user->getNom() . ' (' . $e->getMatricule() . ')'
                        : $e->getMatricule();
                },
                'label'       => 'Employé',
                'placeholder' => '— Sélectionner un employé —',
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner un employé.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventParticipation::class,
        ]);
    }
}
