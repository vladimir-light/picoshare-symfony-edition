<?php

namespace App\Command;

use App\Entity\Entry;
use App\Repository\EntryRepository;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pico:clear-expired-entries',
    description: 'Add a short description for your command',
)]
class PicoClearExpiredEntriesCommand extends Command
{
    private const OPT_SKIP_DB_OPTIMIZATION = 'skip-db-optimization';
    private EntryRepository $entriesRepos;


    public function __construct(
        private readonly EntityManagerInterface $em
    )
    {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->entriesRepos = $this->em->getRepository(Entry::class);
    }


    protected function configure(): void
    {
        $this->addOption(self::OPT_SKIP_DB_OPTIMIZATION, 'S', InputOption::VALUE_NONE, 'Skip DB optimization afterwards (a.k.a. no VACUUM for sqlite Database)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $doOptimizeDb = $input->getOption(self::OPT_SKIP_DB_OPTIMIZATION) === false;

        // 1) delete all expired files
        // 2) if $doOptimizeDb === true perform "optimizations" as last step

        $entriesToDelete = $this->entriesRepos->getAllExpiredEntries(new \DateTimeImmutable('now'));

        $found = count($entriesToDelete);
        if ($found === 0) {
            $io->isVerbose() and $io->note('Nothing to delete ...yet');
            return Command::SUCCESS;
        }

        $processed = 1;
        foreach ($entriesToDelete as $entry) {
            $this->entriesRepos->doDeleteEntryAndAllRelatedData($entry, true);
            $processed++;
        }

        if ($doOptimizeDb) {
            $ok = $this->performeDbOptimization($io, $this->em->getConnection());
            if (!$ok && $io->isVeryVerbose()) {
                $io->writeln('DB optimization NOT executed');
            }
        }

        $io->isVerbose() and $io->success(sprintf('%d entries were successfully deleted', $processed));

        return Command::SUCCESS;
    }

    protected function performeDbOptimization(OutputInterface $output, \Doctrine\DBAL\Connection $dbConn): string|int|false
    {
        if (false === $dbConn->getDriver()->getDatabasePlatform() instanceof SqlitePlatform) {
            $output->isVerbose() and $output->writeln(sprintf('Currently, the DB optimisation is only supported by %s driver. Given: %s.', 'SQLite', $dbConn->getDriver()->getDatabasePlatform()->getName()));
            return false;
        }

      return $dbConn->executeStatement('VACUUM');
    }
}
