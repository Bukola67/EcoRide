<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Carpool;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CarpoolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Vehicle[] $vehicles */
        $vehicles = $options['vehicles'];

        $builder
            ->add('departureCity', TextType::class, [
                'label' => 'Ville de départ',
            ])
            ->add('departureAddress', TextType::class, [
                'label' => 'Adresse de départ',
            ])
            ->add('arrivalCity', TextType::class, [
                'label' => 'Ville d’arrivée',
            ])
            ->add('arrivalAddress', TextType::class, [
                'label' => 'Adresse d’arrivée',
            ])
            ->add('departureAt', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
            ])
            ->add('arrivalAt', DateTimeType::class, [
                'label' => 'Date et heure d’arrivée estimée',
                'widget' => 'single_text',
            ])
            ->add('creditCostPerPassenger', IntegerType::class, [
                'label' => 'Prix par passager (en crédits)',
                'attr' => [
                    'min' => 3,
                ],
                'help' => 'Le prix inclut les 2 crédits prélevés par la plateforme.',
            ])
            ->add('initialSeatCount', IntegerType::class, [
                'label' => 'Nombre de places proposées',
                'attr' => [
                    'min' => 1,
                ],
            ])
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choices' => $vehicles,
                'choice_label' => static function (Vehicle $vehicle): string {
                    return sprintf(
                        '%s — %s',
                        $vehicle->getModel(),
                        $vehicle->getRegistrationNumber()
                    );
                },
                'label' => 'Véhicule utilisé',
                'placeholder' => 'Choisissez un véhicule',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Carpool::class,
            'vehicles' => [],
        ]);

        $resolver->setAllowedTypes('vehicles', 'array');
    }
}