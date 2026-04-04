<?php

namespace App\Form;

use App\Entity\NiveauJeu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NiveauJeuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Facile' => 'FACILE',
                    'Moyen' => 'MOYEN',
                    'Difficile' => 'DIFFICILE',
                ],
                'placeholder' => 'Choisir un niveau',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('minMoyenne', IntegerType::class, [
                'label' => 'Min moyenne',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'placeholder' => 'Ex: 0',
                ],
            ])
            ->add('maxMoyenne', IntegerType::class, [
                'label' => 'Max moyenne',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'placeholder' => 'Ex: 10',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'maxlength' => 255,
                    'placeholder' => 'Décris brièvement ce niveau...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NiveauJeu::class,
        ]);
    }
}