<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\OrderArticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony console command for populating the database with sample orders and articles.
 *
 * Provides repeatable database seeding with predefined 32-character hexadecimal hashes
 * and includes duplicate protection to prevent re-inserting existing records.
 */
#[AsCommand(
    name: 'app:seed-orders',
    description: 'Seeds database with 10 sample orders with realistic 32-char hex hashes, skipping any orders that already exist.'
)]
final class SeedOrdersCommand extends Command
{
    /** @var array<int, string> Predefined list of realistic 32-character hex MD5 hashes for seed orders */
    private const PREDEFINED_HASHES = [
        '0354343235c16cf2f9cb1558bd56d3d1',
        'c81e728d9d4c2f636f067f89cc14862c',
        'eccbc87e4b5ce2fe28308fd9f2a7baf3',
        'a87ff679a2f3e71d9181a67b7542122c',
        'e4da3b7fbbce2345d7772b0674a318d5',
        '1679091c5a880faf6fb5e6087eb1b2dc',
        '8f14e45fceea167a5a36dedd4bea2543',
        'c9f0f895fb98ab9159f51fd0297e236d',
        '45c48cce2e2d7fbcea1afc51c7c6ad26',
        'd3d9446802a44259755d38e6d163e820',
    ];

    /** @var array<int, string> Predefined list of sample client first names */
    private const CLIENT_NAMES = [
        'Alexander',
        'Sergei',
        'Elena',
        'Dmitry',
        'Olga',
        'Maxim',
        'Anna',
        'Viktor',
        'Maria',
        'Pavel'
    ];

    /** @var array<int, string> Predefined list of sample client last names */
    private const CLIENT_SURNAMES = [
        'Dubois',
        'Petrov',
        'Ivanova',
        'Sidorov',
        'Kuznetsova',
        'Smirnov',
        'Vasilieva',
        'Popov',
        'Sokolova',
        'Morozov'
    ];

    /**
     * @param EntityManagerInterface $em Doctrine entity manager for database operations.
     */
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    /**
     * Executes the seed orders console command logic.
     *
     * @param InputInterface $input Console input stream.
     * @param OutputInterface $output Console output stream.
     *
     * @return int Command exit status code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding Database with Sample Orders and Articles');

        // Track seeding statistics.
        $totalOrders = count(self::PREDEFINED_HASHES);
        $createdOrdersCount = 0;
        $skippedOrdersCount = 0;
        $totalArticlesCount = 0;

        $orderRepository = $this->em->getRepository(Order::class);

        // Process each predefined seed order configuration.
        for ($i = 1; $i <= $totalOrders; $i++) {
            $hash = self::PREDEFINED_HASHES[$i - 1];

            // 1. Duplicate check: verify if an order with this exact hash already exists in database.
            $existingOrder = $orderRepository->findOneBy(['hash' => $hash]);

            if ($existingOrder !== null) {
                $io->warning(sprintf('Skipped Order #%d: Hash "%s" already exists in database.', $i, $hash));
                $skippedOrdersCount++;
                continue;
            }

            // 2. Resolve client contact parameters.
            $clientName = self::CLIENT_NAMES[$i - 1];
            $clientSurname = self::CLIENT_SURNAMES[$i - 1];
            $predictableToken = md5('seeded_user_token_' . $i) . md5('token_ext_' . $i);

            // 3. Build new Order entity.
            $order = new Order();
            $order->setName(sprintf('Seeded Order #%d', $i))
                ->setHash($hash)
                ->setToken($predictableToken)
                ->setClientName($clientName)
                ->setClientSurname($clientSurname)
                ->setEmail(sprintf('%s.%s@example.com', strtolower($clientName), strtolower($clientSurname)))
                ->setStatus(($i % 5) + 1)
                ->setPayType(($i % 3) + 1)
                ->setVatType(1)
                ->setLocale('en')
                ->setCurrency('EUR')
                ->setMeasure('m')
                ->setStep(1);

            // 4. Generate randomized order articles (1 to 10 items per order).
            $articlesCount = rand(1, 10);
            $totalArticlesCount += $articlesCount;

            for ($j = 1; $j <= $articlesCount; $j++) {
                $article = new OrderArticle();
                $article->setArticleId(1000 + ($i * 10) + $j)
                    ->setAmount((float) rand(5, 50))
                    ->setPrice((float) (rand(1500, 9999) / 100))
                    ->setCurrency('EUR')
                    ->setMeasure('m');

                $order->addArticle($article);
                $this->em->persist($article);
            }

            // 5. Persist order entity into Doctrine Unit of Work.
            $this->em->persist($order);
            $createdOrdersCount++;

            $io->text(sprintf(
                'Created Order #%d: Hash = "%s", Client = "%s %s", Articles = %d',
                $i,
                $hash,
                $clientName,
                $clientSurname,
                $articlesCount
            ));
        }

        // 6. Flush changes to database if new records were generated.
        if ($createdOrdersCount > 0) {
            $this->em->flush();
            $io->success(sprintf(
                'Seeding complete: Created %d new orders (%d articles total), Skipped %d existing orders.',
                $createdOrdersCount,
                $totalArticlesCount,
                $skippedOrdersCount
            ));
        } else {
            $io->info('All seed orders already exist in the database. No new records were inserted.');
        }

        return Command::SUCCESS;
    }
}
