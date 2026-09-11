<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\Carpool;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // --- Passager uniquement ---
        $passenger1 = new User();
        $passenger1->setEmail('passager1@example.com');
        $passenger1->setUsername('Passager1');
        $passenger1->setPassword(
            $this->passwordHasher->hashPassword($passenger1, 'password123')
        );
        $passenger1->setRoles(['ROLE_USER']);
        $passenger1->setCredits(20);
        $passenger1->setIsDriver(false);
        $passenger1->setIsPassenger(true);
        $passenger1->setIsActive(true);
        $passenger1->setCreatedAt(new \DateTimeImmutable());

        // --- Chauffeur uniquement ---
        $driver1 = new User();
        $driver1->setEmail('chauffeur1@example.com');
        $driver1->setUsername('Chauffeur1');
        $driver1->setPassword(
            $this->passwordHasher->hashPassword($driver1, 'password123')
        );
        $driver1->setRoles(['ROLE_USER']);
        $driver1->setCredits(20);
        $driver1->setIsDriver(true);
        $driver1->setIsPassenger(false);
        $driver1->setIsActive(true);
        $driver1->setCreatedAt(new \DateTimeImmutable());

        // --- Chauffeur + Passager ---
        $driverPassenger = new User();
        $driverPassenger->setEmail('mixte@example.com');
        $driverPassenger->setUsername('Mixte');
        $driverPassenger->setPassword(
            $this->passwordHasher->hashPassword($driverPassenger, 'password123')
        );
        $driverPassenger->setRoles(['ROLE_USER']);
        $driverPassenger->setCredits(20);
        $driverPassenger->setIsDriver(true);
        $driverPassenger->setIsPassenger(true);
        $driverPassenger->setIsActive(true);
        $driverPassenger->setCreatedAt(new \DateTimeImmutable());

        // --- Compte suspendu (chauffeur) ---
        $suspendedDriver = new User();
        $suspendedDriver->setEmail('suspendu@example.com');
        $suspendedDriver->setUsername('Suspendu');
        $suspendedDriver->setPassword(
            $this->passwordHasher->hashPassword($suspendedDriver, 'password123')
        );
        $suspendedDriver->setRoles(['ROLE_USER']);
        $suspendedDriver->setCredits(20);
        $suspendedDriver->setIsDriver(true);
        $suspendedDriver->setIsPassenger(false);
        $suspendedDriver->setIsActive(false);
        $suspendedDriver->setCreatedAt(new \DateTimeImmutable());

        // --- Employé ---
        $employee = new User();
        $employee->setEmail('employee@ecoride.com');
        $employee->setUsername('Employee');
        $employee->setPassword(
            $this->passwordHasher->hashPassword($employee, 'password123')
        );
        $employee->setRoles(['ROLE_EMPLOYEE', 'ROLE_USER']);
        $employee->setCredits(20);
        $employee->setIsDriver(false);
        $employee->setIsPassenger(false);
        $employee->setIsActive(true);
        $employee->setCreatedAt(new \DateTimeImmutable());

        // --- Administrateur ---
        $admin = new User();
        $admin->setEmail('admin@ecoride.com');
        $admin->setUsername('Admin');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'password123')
        );
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setCredits(20);
        $admin->setIsDriver(false);
        $admin->setIsPassenger(false);
        $admin->setIsActive(true);
        $admin->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($passenger1);
        $manager->persist($driver1);
        $manager->persist($driverPassenger);
        $manager->persist($suspendedDriver);
        $manager->persist($employee);
        $manager->persist($admin);

        // --- Véhicules ---

        $vehicle1 = new Vehicle();
        $vehicle1->setOwner($driver1);
        $vehicle1->setRegistrationNumber('AB-123-CD');
        $vehicle1->setFirstRegistrationDate(new \DateTimeImmutable('2020-01-01'));
        $vehicle1->setModel('Toyota Prius');
        $vehicle1->setColor('Gris');
        $vehicle1->setEnergyType('Hybrid');
        $vehicle1->setSeatCount(4);

        $vehicle2 = new Vehicle();
        $vehicle2->setOwner($driverPassenger);
        $vehicle2->setRegistrationNumber('EF-456-GH');
        $vehicle2->setFirstRegistrationDate(new \DateTimeImmutable('2018-06-01'));
        $vehicle2->setModel('Renault Clio');
        $vehicle2->setColor('Bleu');
        $vehicle2->setEnergyType('Diesel');
        $vehicle2->setSeatCount(5);

        $manager->persist($vehicle1);
        $manager->persist($vehicle2);

        // --- Covoiturage ---

        $carpool = new Carpool();
        $carpool->setDriver($driver1);
        $carpool->setVehicle($vehicle1);
        $carpool->setDepartureCity('Paris');
        $carpool->setDepartureAddress('10 rue de Paris, 75001 Paris');
        $carpool->setArrivalCity('Lyon');
        $carpool->setArrivalAddress('1 place Bellecour, 69002 Lyon');
        $carpool->setDepartureAt(new \DateTimeImmutable('+3 days 08:00'));
        $carpool->setArrivalAt(new \DateTimeImmutable('+3 days 12:00'));
        $carpool->setCreditCostPerPassenger(25);
        $carpool->setInitialSeatCount(4);
        $carpool->setRemainingSeatCount(4);
        $carpool->setStatus('PLANNED');
        $carpool->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($carpool);

        $manager->flush();
    }
}