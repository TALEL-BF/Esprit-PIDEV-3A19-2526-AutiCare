<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Evaluation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextareaType::class, [
                'label' => 'Question',
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Entrez votre question',
                    'class' => 'eval-form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La question ne peut pas être vide']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 500,
                        'minMessage' => 'La question doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La question ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('choix1', TextType::class, [
                'label' => 'Choix 1',
                'attr' => [
                    'placeholder' => 'Première option',
                    'class' => 'eval-form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le choix 1 ne peut pas être vide']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le choix 1 ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('choix2', TextType::class, [
                'label' => 'Choix 2',
                'attr' => [
                    'placeholder' => 'Deuxième option',
                    'class' => 'eval-form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le choix 2 ne peut pas être vide']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le choix 2 ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('choix3', TextType::class, [
                'label' => 'Choix 3',
                'attr' => [
                    'placeholder' => 'Troisième option',
                    'class' => 'eval-form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le choix 3 ne peut pas être vide']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le choix 3 ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('bonneReponse', ChoiceType::class, [
                'label' => 'Bonne réponse',
                'choices' => [
                    'Choix 1' => 'choix1',
                    'Choix 2' => 'choix2',
                    'Choix 3' => 'choix3',
                ],
                'attr' => ['class' => 'eval-form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner la bonne réponse'])
                ]
            ])
            ->add('score', IntegerType::class, [
                'label' => 'Score (points)',
                'attr' => [
                    'placeholder' => 'Ex: 1, 2, 5...',
                    'min' => 1,
                    'max' => 100,
                    'class' => 'eval-form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le score est requis']),
                    new Assert\Positive(['message' => 'Le score doit être un nombre positif']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                        'notInRangeMessage' => 'Le score doit être compris entre {{ min }} et {{ max }} points'
                    ])
                ]
            ])
            ->add('cours', EntityType::class, [
                'label' => 'Cours',
                'class' => Cours::class,
                'choice_label' => function(Cours $cours) {
                    return $cours->getTitre() . ' (' . $cours->getNiveau() . ')';
                },
                'attr' => ['class' => 'eval-form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un cours'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'evaluation_item',
        ]);
    }
}