<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingControllerTest extends WebTestCase
{
    public function testVisitorMustBeAuthenticatedToBook(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);

        $client->request('POST', '/carpools/1/book');

        self::assertResponseRedirects('/login');
    }
}
