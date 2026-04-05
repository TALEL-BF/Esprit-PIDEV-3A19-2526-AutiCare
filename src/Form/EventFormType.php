<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Sponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Atelier peinture sensorielle']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Description de l\'événement...']
            ])
            ->add('typeEvent', TextType::class, [
                'label' => 'Type',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Atelier, Conférence, Sortie, Formation']
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse ou lieu en ligne']
            ])
            ->add('maxParticipant', NumberType::class, [
                'label' => 'Capacité max',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre de participants']
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*']
            ])
            ->add('status', TextType::class, [
                'label' => 'Statut',
                'attr' => ['class' => 'form-control']
            ])
            ->add('sponsors', EntityType::class, [
                'class' => Sponsor::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'form-select', 'multiple' => 'multiple']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}