<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testNewUserHasTwentyCreditsByDefault(): void
    {
        $user = new User();

        self::assertSame(20, $user->getCredits());
    }

    public function testCreditsCannotBeNegative(): void
    {
        $user = new User();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le solde de crédits ne peut pas être négatif.'
        );

        $user->setCredits(-1);
    }

    public function testUserAlwaysHasRoleUser(): void
    {
        $user = new User();
        $user->setRoles([]);

        self::assertContains('ROLE_USER', $user->getRoles());
    }
}
