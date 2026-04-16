<?php

namespace App\Form;

use App\Entity\GameSession;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameSessionType extends AbstractType
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
            ->add('gameTitle', TextType::class, [
                'label' => 'Jeu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Memory colore',
                ],
            ])
            ->add('skill', ChoiceType::class, [
                'label' => 'Competence',
                'choices' => [
                    'Memoire' => 'memoire',
                    'Social' => 'social',
                    'Communication' => 'communication',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('score', NumberType::class, [
                'label' => 'Score obtenu',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => 0,
                    'max' => 100,
                ],
            ])
            ->add('maxScore', NumberType::class, [
                'label' => 'Score maximal',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => 1,
                ],
            ])
            ->add('durationSeconds', IntegerType::class, [
                'label' => 'Temps en secondes',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Ex: 420',
                ],
            ])
            ->add('playedAt', DateTimeType::class, [
                'label' => 'Date de jeu',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Observation',
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
            'data_class' => GameSession::class,
        ]);
    }
}
