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

        $vehicle3 = new Vehicle();
        $vehicle3->setOwner($driver1);
        $vehicle3->setRegistrationNumber('GH-789-IJ');
        $vehicle3->setFirstRegistrationDate(
            new \DateTimeImmutable('2023-04-10')
        );
        $vehicle3->setModel('Tesla Model 3');
        $vehicle3->setColor('Blanc');
        $vehicle3->setEnergyType('Electric');
        $vehicle3->setSeatCount(4);

        $vehicle4 = new Vehicle();
        $vehicle4->setOwner($driverPassenger);
        $vehicle4->setRegistrationNumber('KL-012-MN');
        $vehicle4->setFirstRegistrationDate(
            new \DateTimeImmutable('2022-09-15')
        );
        $vehicle4->setModel('Peugeot e-208');
        $vehicle4->setColor('Vert');
        $vehicle4->setEnergyType('Electric');
        $vehicle4->setSeatCount(4);

        $manager->persist($vehicle1);
        $manager->persist($vehicle2);
        $manager->persist($vehicle3);
        $manager->persist($vehicle4);

        // --- Covoiturage ---
        // --- Covoiturages disponibles : Paris → Lyon, dans 3 jours ---

        $departureDate = new \DateTimeImmutable('+3 days 08:00');

        $carpool1 = new Carpool();
        $carpool1->setDriver($driver1);
        $carpool1->setVehicle($vehicle1);
        $carpool1->setDepartureCity('Paris');
        $carpool1->setDepartureAddress('10 rue de Paris, 75001 Paris');
        $carpool1->setArrivalCity('Lyon');
        $carpool1->setArrivalAddress('1 place Bellecour, 69002 Lyon');
        $carpool1->setDepartureAt($departureDate);
        $carpool1->setArrivalAt(new \DateTimeImmutable('+3 days 12:00'));
        $carpool1->setCreditCostPerPassenger(25);
        $carpool1->setInitialSeatCount(4);
        $carpool1->setRemainingSeatCount(3);
        $carpool1->setStatus('PLANNED');
        $carpool1->setCreatedAt(new \DateTimeImmutable());

        $carpool2 = new Carpool();
        $carpool2->setDriver($driverPassenger);
        $carpool2->setVehicle($vehicle4);
        $carpool2->setDepartureCity('Paris');
        $carpool2->setDepartureAddress('Gare de Lyon, 75012 Paris');
        $carpool2->setArrivalCity('Lyon');
        $carpool2->setArrivalAddress('Gare Part-Dieu, 69003 Lyon');
        $carpool2->setDepartureAt(new \DateTimeImmutable('+3 days 09:30'));
        $carpool2->setArrivalAt(new \DateTimeImmutable('+3 days 13:15'));
        $carpool2->setCreditCostPerPassenger(28);
        $carpool2->setInitialSeatCount(3);
        $carpool2->setRemainingSeatCount(2);
        $carpool2->setStatus('PLANNED');
        $carpool2->setCreatedAt(new \DateTimeImmutable());

        // Trajet complet : doit être exclu par la recherche.
        $fullCarpool = new Carpool();
        $fullCarpool->setDriver($driver1);
        $fullCarpool->setVehicle($vehicle3);
        $fullCarpool->setDepartureCity('Paris');
        $fullCarpool->setDepartureAddress('Porte d’Orléans, 75014 Paris');
        $fullCarpool->setArrivalCity('Lyon');
        $fullCarpool->setArrivalAddress('Place des Terreaux, 69001 Lyon');
        $fullCarpool->setDepartureAt(new \DateTimeImmutable('+3 days 11:00'));
        $fullCarpool->setArrivalAt(new \DateTimeImmutable('+3 days 15:00'));
        $fullCarpool->setCreditCostPerPassenger(22);
        $fullCarpool->setInitialSeatCount(3);
        $fullCarpool->setRemainingSeatCount(0);
        $fullCarpool->setStatus('PLANNED');
        $fullCarpool->setCreatedAt(new \DateTimeImmutable());

        // Trajet annulé : doit être exclu par la recherche.
        $cancelledCarpool = new Carpool();
        $cancelledCarpool->setDriver($driverPassenger);
        $cancelledCarpool->setVehicle($vehicle2);
        $cancelledCarpool->setDepartureCity('Paris');
        $cancelledCarpool->setDepartureAddress('Place de la République, 75011 Paris');
        $cancelledCarpool->setArrivalCity('Lyon');
        $cancelledCarpool->setArrivalAddress('Vieux Lyon, 69005 Lyon');
        $cancelledCarpool->setDepartureAt(new \DateTimeImmutable('+3 days 14:00'));
        $cancelledCarpool->setArrivalAt(new \DateTimeImmutable('+3 days 18:30'));
        $cancelledCarpool->setCreditCostPerPassenger(20);
        $cancelledCarpool->setInitialSeatCount(4);
        $cancelledCarpool->setRemainingSeatCount(3);
        $cancelledCarpool->setStatus('CANCELLED');
        $cancelledCarpool->setCreatedAt(new \DateTimeImmutable());

        // Premier trajet futur à proposer quand aucun résultat n'existe à la date recherchée.
        $alternativeCarpool = new Carpool();
        $alternativeCarpool->setDriver($driverPassenger);
        $alternativeCarpool->setVehicle($vehicle4);
        $alternativeCarpool->setDepartureCity('Paris');
        $alternativeCarpool->setDepartureAddress('Bercy Seine, 75012 Paris');
        $alternativeCarpool->setArrivalCity('Lyon');
        $alternativeCarpool->setArrivalAddress('Perrache, 69002 Lyon');
        $alternativeCarpool->setDepartureAt(new \DateTimeImmutable('+7 days 08:30'));
        $alternativeCarpool->setArrivalAt(new \DateTimeImmutable('+7 days 12:45'));
        $alternativeCarpool->setCreditCostPerPassenger(26);
        $alternativeCarpool->setInitialSeatCount(4);
        $alternativeCarpool->setRemainingSeatCount(4);
        $alternativeCarpool->setStatus('PLANNED');
        $alternativeCarpool->setCreatedAt(new \DateTimeImmutable());

        // Autre itinéraire pour vérifier que les villes sont réellement filtrées.
        $bordeauxToulouseCarpool = new Carpool();
        $bordeauxToulouseCarpool->setDriver($driver1);
        $bordeauxToulouseCarpool->setVehicle($vehicle3);
        $bordeauxToulouseCarpool->setDepartureCity('Bordeaux');
        $bordeauxToulouseCarpool->setDepartureAddress('Gare Saint-Jean, 33800 Bordeaux');
        $bordeauxToulouseCarpool->setArrivalCity('Toulouse');
        $bordeauxToulouseCarpool->setArrivalAddress('Gare Matabiau, 31000 Toulouse');
        $bordeauxToulouseCarpool->setDepartureAt(new \DateTimeImmutable('+3 days 10:00'));
        $bordeauxToulouseCarpool->setArrivalAt(new \DateTimeImmutable('+3 days 12:30'));
        $bordeauxToulouseCarpool->setCreditCostPerPassenger(18);
        $bordeauxToulouseCarpool->setInitialSeatCount(4);
        $bordeauxToulouseCarpool->setRemainingSeatCount(3);
        $bordeauxToulouseCarpool->setStatus('PLANNED');
        $bordeauxToulouseCarpool->setCreatedAt(new \DateTimeImmutable());

        // --- Passager sans crédit ---
        $passengerWithoutCredits = new User();
        $passengerWithoutCredits->setEmail('sans-credit@example.com');
        $passengerWithoutCredits->setUsername('SansCredit');
        $passengerWithoutCredits->setPassword(
            $this->passwordHasher->hashPassword(
                $passengerWithoutCredits,
                'password123'
            )
        );
        $passengerWithoutCredits->setRoles(['ROLE_USER']);
        $passengerWithoutCredits->setCredits(0);
        $passengerWithoutCredits->setIsDriver(false);
        $passengerWithoutCredits->setIsPassenger(true);
        $passengerWithoutCredits->setIsActive(true);
        $passengerWithoutCredits->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($passengerWithoutCredits);

        $manager->persist($carpool1);
        $manager->persist($carpool2);
        $manager->persist($fullCarpool);
        $manager->persist($cancelledCarpool);
        $manager->persist($alternativeCarpool);
        $manager->persist($bordeauxToulouseCarpool);

        $manager->flush();
    }
}