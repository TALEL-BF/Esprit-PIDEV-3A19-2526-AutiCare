<?php

namespace App\Form;

use App\Entity\Rdv;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RdvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isCreate = (bool) $options['is_create'];

        $builder
            ->add('typeConsultation', ChoiceType::class, [
                'label' => 'Type de consultation',
                'choices' => [
                    'Première consultation' => 'premiere_consultation',
                    'Suivi' => 'suivi',
                    'Urgence' => 'urgence',
                    'Familiale' => 'familiale',
                    'Bilan' => 'bilan',
                ],
                'placeholder' => 'Sélectionner un type',
                'data' => $isCreate ? null : $builder->getData()?->getTypeConsultation(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un type de consultation.']),
                ],
            ])
            ->add('dateHeureRdv', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'jj/mm/aaaa hh:mm',
                    'min' => (new \DateTime())->format('Y-m-d\\TH:i'),
                ],
                'data' => $isCreate ? null : $builder->getData()?->getDateHeureRdv(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner la date et l\'heure du rendez-vous.']),
                    new Assert\Type(['type' => \DateTimeInterface::class, 'message' => 'Le format de date/heure est invalide.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'now',
                        'message' => 'La date et l\'heure du rendez-vous ne peuvent pas etre dans le passe.',
                    ]),
                ],
            ])
            ->add('statutRdv', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Planifiée' => 'planifiee',
                    'Confirmé' => 'confirme',
                    'Annulé' => 'annule',
                    'Reporté' => 'reporte',
                    'Terminé' => 'termine',
                ],
                'placeholder' => 'Sélectionner un statut',
                'data' => $isCreate ? null : $builder->getData()?->getStatutRdv(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un statut.']),
                ],
            ])
            ->add('dureeRdvMinutes', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr' => ['placeholder' => 'Ex: 45'],
                'data' => $isCreate ? null : $builder->getData()?->getDureeRdvMinutes(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer une durée.']),
                    new Assert\Positive(['message' => 'La durée doit être un nombre positif.']),
                    new Assert\Range([
                        'min' => 15,
                        'max' => 240,
                        'notInRangeMessage' => 'La durée doit être comprise entre {{ min }} et {{ max }} minutes.',
                    ]),
                ],
            ])
            ->add('idPsychologue', ChoiceType::class, [
                'label' => 'Psychologue',
                'choices' => [
                    'Dr. Fatma Trabelsi' => 1,
                    'Dr. Sana Mejri' => 2,
                    'Dr. Amine Karray' => 3,
                ],
                'placeholder' => 'Sélectionner un psychologue',
                'data' => $isCreate ? null : $builder->getData()?->getIdPsychologue(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un psychologue.']),
                ],
            ])
            ->add('idAutiste', ChoiceType::class, [
                'label' => 'Patient',
                'choices' => [
                    'Adam Mejri' => 1,
                    'Sara Hamdi' => 2,
                    'Youssef Ben Amor' => 3,
                    'Nour Gharbi' => 4,
                ],
                'placeholder' => 'Sélectionner un patient',
                'data' => $isCreate ? null : $builder->getData()?->getIdAutiste(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un patient.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rdv::class,
            'is_create' => false,
        ]);

        $resolver->setAllowedTypes('is_create', 'bool');
    }
}
