<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use App\Enum\PostRideValidation;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_booking_passenger_carpool',
    fields: ['passenger', 'carpool']
)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passenger = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Carpool $carpool = null;

    #[ORM\Column(length: 20, enumType: PostRideValidation::class)]
    private PostRideValidation $postRideValidation = PostRideValidation::Pending;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPassenger(): ?User
    {
        return $this->passenger;
    }

    public function setPassenger(?User $passenger): static
    {
        $this->passenger = $passenger;

        return $this;
    }

    public function getCarpool(): ?Carpool
    {
        return $this->carpool;
    }

    public function setCarpool(?Carpool $carpool): static
    {
        $this->carpool = $carpool;

        return $this;
    }
    public function getPostRideValidation(): PostRideValidation
    {
        return $this->postRideValidation;
    }

    public function setPostRideValidation(
        PostRideValidation $postRideValidation
    ): static {
        $this->postRideValidation = $postRideValidation;

        return $this;
    }
}
