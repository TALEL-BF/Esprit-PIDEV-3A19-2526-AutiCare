<?php

namespace App\Form;

use App\Entity\Seance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SeanceType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$isCreate = (bool) $options['is_create'];

		$builder
			->add('titreSeance', TextType::class, [
				'label' => 'Titre de la séance',
				'attr' => ['placeholder' => 'Ex: Atelier communication'],
				'data' => $isCreate ? null : $builder->getData()?->getTitreSeance(),
				'constraints' => [
					new Assert\NotBlank(['message' => 'Veuillez renseigner le titre de la séance.']),
					new Assert\Length([
						'max' => 100,
						'maxMessage' => 'Le titre ne doit pas dépasser {{ limit }} caractères.',
					]),
				],
			])
			->add('dateSeance', DateTimeType::class, [
				'label' => 'Date et heure',
				'widget' => 'single_text',
				'attr' => [
					'min' => (new \DateTime())->format('Y-m-d\\TH:i'),
				],
				'data' => $isCreate ? null : $builder->getData()?->getDateSeance(),
				'constraints' => [
					new Assert\NotBlank(['message' => 'Veuillez renseigner la date et l\'heure de la séance.']),
					new Assert\Type(['type' => \DateTimeInterface::class, 'message' => 'Le format de date/heure est invalide.']),
					new Assert\GreaterThanOrEqual([
						'value' => 'now',
						'message' => 'La date et l\'heure de la séance ne peuvent pas être dans le passé.',
					]),
				],
			])
			->add('joursSemaine', ChoiceType::class, [
				'label' => 'Jour',
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
				'data' => $isCreate ? null : $builder->getData()?->getJoursSemaine(),
				'constraints' => [
					new Assert\NotBlank(['message' => 'Veuillez sélectionner un jour.']),
				],
			])
			->add('duree', IntegerType::class, [
				'label' => 'Durée (minutes)',
				'attr' => ['placeholder' => 'Ex: 60'],
				'data' => $isCreate ? null : $builder->getData()?->getDuree(),
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
			->add('statutSeance', ChoiceType::class, [
				'label' => 'Statut',
				'choices' => [
					'Planifiée' => 'planifiee',
					'Confirmée' => 'confirme',
					'Annulée' => 'annule',
					'Reportée' => 'reporte',
					'Terminée' => 'termine',
				],
				'placeholder' => 'Sélectionner un statut',
				'data' => $isCreate ? null : $builder->getData()?->getStatutSeance(),
				'constraints' => [
					new Assert\NotBlank(['message' => 'Veuillez sélectionner un statut.']),
				],
			])
			->add('idAutiste', IntegerType::class, [
				'label' => 'Patient',
				'attr' => ['placeholder' => 'ID patient'],
				'data' => $isCreate ? null : $builder->getData()?->getIdAutiste(),
				'constraints' => [
					new Assert\NotBlank(['message' => 'Veuillez indiquer le patient.']),
					new Assert\Positive(['message' => 'L\'ID patient doit être un entier positif.']),
				],
			])
			->add('idProfesseur', IntegerType::class, [
				'label' => 'Professeur',
				'attr' => ['placeholder' => 'ID professeur'],
				'data' => $isCreate ? null : $builder->getData()?->getIdProfesseur(),
				'constraints' => [
					new Assert\NotBlank(['message' => 'Veuillez indiquer le professeur.']),
					new Assert\Positive(['message' => 'L\'ID professeur doit être un entier positif.']),
				],
			])
			->add('idCours', IntegerType::class, [
				'label' => 'Cours',
				'attr' => ['placeholder' => 'ID cours'],
				'data' => $isCreate ? null : $builder->getData()?->getIdCours(),
				'constraints' => [
					new Assert\NotBlank(['message' => 'Veuillez indiquer le cours.']),
					new Assert\Positive(['message' => 'L\'ID cours doit être un entier positif.']),
				],
			])
			->add('description', TextareaType::class, [
				'label' => 'Description',
				'required' => false,
				'attr' => ['rows' => 3, 'placeholder' => 'Notes complémentaires...'],
				'data' => $isCreate ? null : $builder->getData()?->getDescription(),
				'constraints' => [
					new Assert\Length([
						'max' => 2000,
						'maxMessage' => 'La description ne doit pas dépasser {{ limit }} caractères.',
					]),
				],
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Seance::class,
			'is_create' => false,
		]);

		$resolver->setAllowedTypes('is_create', 'bool');
	}
}
