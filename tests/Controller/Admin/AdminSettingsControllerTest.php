<?php

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class AdminSettingsControllerTest extends WebTestCase
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
    public function testIndex(): void
    {
        $this->markTestIncomplete('not implemented yet.');
        $this->client->request('GET', '/settings');

        self::assertResponseIsSuccessful();
    }
}
