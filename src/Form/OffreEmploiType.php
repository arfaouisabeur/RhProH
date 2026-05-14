<?php

namespace App\Form;

use App\Entity\OffreEmploi;
use App\Entity\RH;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffreEmploiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du poste',
                'required' => true,
                'empty_data' => '',
                'attr'  => [
                    'placeholder'  => 'Ex: Développeur Full Stack',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'required' => true,
                'empty_data' => '',
                'attr'  => [
                    'id'          => 'f_localisation',
                    'placeholder' => 'Ex: Tunis, Tunisie',
                ],
            ])
            ->add('typeContrat', ChoiceType::class, [
                'label'       => 'Type de contrat',
                'required' => true,
                'choices'     => [
                    'CDI'        => 'CDI',
                    'CDD'        => 'CDD',
                    'Stage'      => 'Stage',
                    'Alternance' => 'Alternance',
                ],
                'placeholder' => '— Choisir un type —',
            ])
            ->add('statut', ChoiceType::class, [
                'label'       => 'Statut',
                'required' => true,
                'choices'     => [
                    'Ouverte' => 'Ouverte',
                    'Fermée'  => 'Fermée',
                ],
                'placeholder' => '— Choisir un statut —',
            ])
            ->add('datePublication', DateType::class, [
                'label'  => 'Date de publication',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('dateExpiration', DateType::class, [
                'label'  => 'Date d\'expiration',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du poste',
                'required' => true,
                'empty_data' => '',
                'attr'  => [
                    'rows'        => 6,
                    'placeholder' => 'Décrivez le poste, les missions, les compétences requises...',
                ],
            ])
            // ── Champs cachés — remplis automatiquement par le JS géocodage ──
            ->add('latitude', HiddenType::class, [
                'required' => false,
                'attr'     => ['id' => 'f_latitude'],
            ])
            ->add('longitude', HiddenType::class, [
                'required' => false,
                'attr'     => ['id' => 'f_longitude'],
            ]);

        if ($options['show_rh_field']) {
            $builder->add('rh', EntityType::class, [
                'label'        => 'RH responsable',
                'class'        => RH::class,
                'choice_label' => fn(RH $rh) => $rh->getUser()?->getEmail() ?? 'RH #' . $rh->getUserId(),
                'placeholder'  => '— Choisir un RH —',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'    => OffreEmploi::class,
            'show_rh_field' => false,
        ]);
        $resolver->setAllowedTypes('show_rh_field', 'bool');
    }
}
