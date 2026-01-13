<?php

namespace App\Tests\Controller;

use App\Entity\GuestLink;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class UploadControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    public function setUp(): void
    {

        $this->client = static::createClient([], [
            'HTTP_HOST' => 'localhost:8000',
        ]);

    }

    public function testUploadAsAdmin(): void
    {
        $testAdmin = new InMemoryUser('test_admin', 'password', ['ROLE_ADMIN']);
        $this->client->loginUser($testAdmin);
        $this->client->request('GET', '/upload');

        self::assertResponseIsSuccessful();
    }

    public function testUploadAsAnonUser(): void
    {
        $this->client->request('GET', '/upload');

        self::assertResponseStatusCodeSame(404);
    }

    public function testUploadViaGuestLink(): void
    {
        $this->markTestIncomplete('not implemented yet.');

        /** @var GuestLink $guestLink */
        $this->client->request('GET', '/g/' . $guestLink->getUniqLinkId()->toBase58());

        self::assertResponseIsSuccessful();
    }
}
