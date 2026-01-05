<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicPagesControllerTest extends WebTestCase
{
    public function setUp(): void
    {
        $this->client = static::createClient([], [
            'HTTP_HOST' => 'localhost:8000',
        ]);
    }

    public function testHomepage(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testAbout(): void
    {
        $this->client->request('GET', '/about');

        self::assertResponseIsSuccessful();
    }
}
