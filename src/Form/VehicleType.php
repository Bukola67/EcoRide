<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('registrationNumber', TextType::class, [
                'label' => 'Plaque d’immatriculation',
            ])
            ->add('firstRegistrationDate', DateType::class, [
                'label' => 'Date de première immatriculation',
                'widget' => 'single_text',
            ])
            ->add('model', TextType::class, [
                'label' => 'Modèle',
            ])
            ->add('color', TextType::class, [
                'label' => 'Couleur',
                'required' => false,
            ])
            ->add('energyType', ChoiceType::class, [
                'label' => 'Énergie',
                'choices' => [
                    'Essence' => 'essence',
                    'Diesel' => 'diesel',
                    'Électrique' => 'electric',
                    'Hybride' => 'hybrid',
                    'GPL' => 'lpg',
                ],
            ])
            ->add('seatCount', IntegerType::class, [
                'label' => 'Nombre de places',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}