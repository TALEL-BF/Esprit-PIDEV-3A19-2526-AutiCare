<?php

namespace App\Form;

use App\Entity\ClinicalNote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClinicalNoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enfantId', IntegerType::class, [
                'label' => 'Id enfant',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                ],
            ])
            ->add('sessionDate', DateTimeType::class, [
                'label' => 'Date de seance',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('sessionNote', TextareaType::class, [
                'label' => 'Notes de seance',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('proposedAction', TextareaType::class, [
                'label' => 'Action proposee',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('resultAfterAction', TextareaType::class, [
                'label' => 'Resultat apres action',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClinicalNote::class,
        ]);
    }
}
