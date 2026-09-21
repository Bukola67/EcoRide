<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function testInactiveUserIsRejectedBeforeAuthentication(): void
    {
        $user = new User();
        $user->setIsActive(false);

        $checker = new UserChecker();

        $this->expectException(
            CustomUserMessageAccountStatusException::class
        );

        $checker->checkPreAuth($user);
    }

    public function testActiveUserCanAuthenticate(): void
    {
        $user = new User();
        $user->setIsActive(true);

        $checker = new UserChecker();

        $checker->checkPreAuth($user);

        self::assertTrue(true);
    }
}
