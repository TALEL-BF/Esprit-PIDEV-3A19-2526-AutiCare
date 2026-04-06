<?php

namespace App\Form;

use App\Entity\SuiviTotal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SuiviTotalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enfantId', IntegerType::class, [
                'label' => 'Id enfant',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Ex: 1',
                ],
            ])
            ->add('noteCours', NumberType::class, [
                'label' => 'Note de cours',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'step' => '0.01',
                    'placeholder' => 'Ex: 12.50',
                ],
            ])
            ->add('noteConsultation', NumberType::class, [
                'label' => 'Note de consultation',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'step' => '0.01',
                    'placeholder' => 'Ex: 14.00',
                ],
            ])
            ->add('remarque', TextareaType::class, [
                'label' => 'Remarque',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'maxlength' => 255,
                    'placeholder' => 'Remarque facultative...',
                ],
            ])
            ->add('moyenne', NumberType::class, [
                'property_path' => 'moyenneGenerale',
                'disabled' => true,
                'label' => 'Moyenne générale',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SuiviTotal::class,
        ]);
    }
}