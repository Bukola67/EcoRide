<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('username', TextType::class, [
                'label' => 'Nom d’utilisateur',
            ])
            ->add('isDriver', CheckboxType::class, [
                'label' => 'Je souhaite être chauffeur',
                'required' => false,
            ])
            ->add('isPassenger', CheckboxType::class, [
                'label' => 'Je souhaite être passager',
                'required' => false,
            ])
            ->add('acceptsSmokers', CheckboxType::class, [
                'label' => 'J’accepte les fumeurs',
                'required' => false,
            ])
            ->add('acceptsPets', CheckboxType::class, [
                'label' => 'J’accepte les animaux',
                'required' => false,
            ])
            ->add('customPreferences', TextType::class, [
                'label' => 'Autres préférences (texte libre)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}