<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Entry;
use App\Tests\Factory\EntryFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Zenstruck\Foundry\Test as FoundryTestHelpers;


final class FilesControllerTest extends WebTestCase
{
    use FoundryTestHelpers\Factories;
    use FoundryTestHelpers\ResetDatabase;

    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        $testAdmin = new InMemoryUser('test_admin', 'password', ['ROLE_ADMIN']);
        $this->client = static::createClient([], [
            'HTTP_HOST' => 'localhost:8000',
            //'HTTP_HOST' => 'sf-picoshare.lan',
        ]);
        $this->client->loginUser($testAdmin);
    }

    public function testIndexAction(): void
    {
        $this->client->request('GET', '/files');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('– Files');
        self::assertSelectorCount(1, '.table-container');
        self::assertSelectorTextSame('.subtitle', 'Manage uploaded files');
    }

    public function testIndexActionWithFilesEntries(): void
    {
        $filesCount = 5;
        EntryFactory::createMany($filesCount);

        $this->client->request('GET', '/files');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('– Files');
        self::assertSelectorCount(1, '.table-container');

        // table-contents
        self::assertSelectorCount($filesCount, '.table-container > .table tbody > tr');
    }

    public function testInfoFileAction(): void
    {
        [$fileUniqId,] = $this->_makeTestFile('archive.zip');

        $url = '/files/' . $fileUniqId . '/info';
        $this->client->request('GET', $url);
        //
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('– File Info');
        self::assertAnySelectorTextContains('.title.mt-5', 'File Info');
        self::assertSelectorCount(2, '.box');
    }


    public function testEditFileAction(): void
    {
        [$fileUniqId,] = $this->_makeTestFile('btc_wallet.dat');

        $url = '/files/' . $fileUniqId . '/edit';
        $this->client->request('GET', $url);
        //
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('– Edit File');
        self::assertSelectorCount(1, "form[action='{$url}']");
        self::assertSelectorCount(1, "#edit_entry_filename"); // filename form-field
        self::assertInputValueSame('edit_entry[filename]', 'btc_wallet.dat');
    }

    public function testConfirmDeleteAction(): void
    {
        [$fileUniqId,] = $this->_makeTestFile('picture.jpg');

        $url = '/files/' . $fileUniqId . '/confirm-delete';
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('– Delete File');
        self::assertSelectorCount(1, "h1.title");
        self::assertSelectorTextSame('h1.title', 'Delete File');
        self::assertInputValueSame('form[entryId]', $fileUniqId);
        self::assertSelectorTextSame('.main-content .entry-filename', 'picture.jpg');
    }

    public function testFileDeletedAction(): void
    {
        [$fileUniqId,] = $this->_makeTestFile('archive.zip');

        $url = '/files/' . $fileUniqId . '/confirm-delete';
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();

    }

    public function testFileDeletionFailedAction(): void
    {
        self::markTestIncomplete('TBD...');
    }

    public function testFileDownloadsAction(): void
    {
        [$fileUniqId,] = $this->_makeTestFile('image.png');

        $url = '/files/' . $fileUniqId . '/downloads';
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('– Downloads');
        self::assertSelectorTextSame('.subtitle', 'image.png');
    }

    /**
     * @param string $filename
     * @phpstan-return array{0: string, 1: Entry}
     * @return array
     *
     */
    private function _makeTestFile(string $filename): array
    {
        $fakeFile = EntryFactory::createOne([
            'filename' => $filename,
        ]);

        $fileUniqId = $fakeFile->getUniqLinkId()->toBase58();
        return [$fileUniqId, $fakeFile];
    }
}
