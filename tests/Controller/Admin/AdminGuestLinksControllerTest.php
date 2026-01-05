<?php

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class AdminGuestLinksControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        $testAdmin = new InMemoryUser('test_admin', 'password', ['ROLE_ADMIN']);
        $this->client = static::createClient([], [
            'HTTP_HOST' => 'localhost:8000',
        ]);
        $this->client->loginUser($testAdmin);
    }

    public function testIndexAction(): void
    {
        $this->client->request('GET', '/guest-links');

        self::assertResponseIsSuccessful();
    }
}
