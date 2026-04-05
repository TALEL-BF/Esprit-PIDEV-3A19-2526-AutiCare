<?php

namespace App\Form;

use App\Entity\EmploiDuTemps;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EmploiDuTempsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isCreate = (bool) $options['is_create'];

        $builder
            ->add('jourSemaine', ChoiceType::class, [
                'label' => 'Jour semaine',
                'choices' => [
                    'Lundi' => 'lundi',
                    'Mardi' => 'mardi',
                    'Mercredi' => 'mercredi',
                    'Jeudi' => 'jeudi',
                    'Vendredi' => 'vendredi',
                    'Samedi' => 'samedi',
                    'Dimanche' => 'dimanche',
                ],
                'placeholder' => 'Sélectionner un jour',
                'data' => $isCreate ? null : $builder->getData()?->getJourSemaine(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un jour de semaine.']),
                ],
            ])
            ->add('trancheHoraire', ChoiceType::class, [
                'label' => 'Tranche horaire',
                'choices' => [
                    'Matin' => 'matin',
                    'Après-midi' => 'apres_midi',
                    'Soir' => 'soir',
                    'Journée' => 'journee',
                ],
                'placeholder' => 'Sélectionner une tranche',
                'data' => $isCreate ? null : $builder->getData()?->getTrancheHoraire(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une tranche horaire.']),
                ],
            ])
            ->add('rdvSelection', ChoiceType::class, [
                'label' => 'RDV',
                'mapped' => false,
                'required' => false,
                'placeholder' => 'Aucun',
                'choices' => $options['rdv_choices'],
                'help' => 'Sélectionnez un RDV OU une séance, pas les deux.',
            ])
            ->add('seanceSelection', ChoiceType::class, [
                'label' => 'Séance',
                'mapped' => false,
                'required' => false,
                'placeholder' => 'Aucune',
                'choices' => $options['seance_choices'],
                'help' => 'Sélectionnez une séance OU un RDV, pas les deux.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmploiDuTemps::class,
            'rdv_choices' => [],
            'seance_choices' => [],
            'is_create' => false,
        ]);

        $resolver->setAllowedTypes('rdv_choices', 'array');
        $resolver->setAllowedTypes('seance_choices', 'array');
        $resolver->setAllowedTypes('is_create', 'bool');
    }
}
