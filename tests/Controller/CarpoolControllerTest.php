<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CarpoolControllerTest extends WebTestCase
{
    public function testVisitorCanAccessCarpoolSearch(): void
    {
        $client = static::createClient();

        $client->request('GET', '/carpools');

        self::assertResponseIsSuccessful();
    }
}
