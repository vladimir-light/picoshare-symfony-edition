<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Entry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class AdminFilesControllerTest extends WebTestCase
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
        $this->client->request('GET', '/files');

        self::assertResponseIsSuccessful();
    }

    public function testInfoFileAction(): void
    {
        $this->markTestIncomplete('not implemented yet. Add `zenstruck/foundry` for DB-fixtures');
        /** @var Entry $dummyFile */
        $dummyFile = null;
        $this->client->request('GET', '/files/' . $dummyFile->getUniqLinkId()->toBase58() . '/info');

        self::assertResponseIsSuccessful();
    }
}
