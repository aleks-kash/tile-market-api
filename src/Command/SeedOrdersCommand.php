<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\OrderArticle;
use App\Enum\CurrencySid;
use App\Enum\LocaleSid;
use App\Enum\MeasureSid;
use App\Enum\PayTypeSid;
use App\Enum\StatusSid;
use App\Enum\StepSid;
use App\Enum\VatTypeSid;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Symfony console command for populating the database with sample orders and articles.
 *
 * Provides repeatable database seeding with a predefined 32-character hexadecimal hash
 * for the default first order and Faker-generated data for subsequent orders,
 * including duplicate protection to prevent re-inserting existing records.
 */
#[AsCommand(
    name: 'app:seed-orders',
    description: 'Seeds database with 10 sample orders with realistic 32-char hex hashes, skipping any orders that already exist.'
)]
final class SeedOrdersCommand extends Command
{
    /**
     * @var string Predefined 32-character hex hash for the default first seed order
     */
    public const string PREDEFINED_HASH = '0354343235c16cf2f9cb1558bd56d3d1';

    /**
     * @var string Predefined client first name for the default first seed order
     */
    public const string CLIENT_NAME = 'Alexander';

    /**
     * @var string Predefined client last name for the default first seed order
     */
    public const string CLIENT_SURNAME = 'Dubois';

    /**
     * @var string Predefined client email for the default first seed order
     */
    public const string CLIENT_EMAIL = 'tile.expert@example.com';

    /**
     * @var string Predefined order description for testing
     */
    public const string ORDER_DESCRIPTION = 'Sample order description for testing';

    /**
     * @var int Total number of sample orders to generate
     */
    private const int TOTAL_ORDERS = 10;

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

        $faker = Factory::create();
        $faker->seed(12345);

        // Track seeding statistics.
        $totalOrders = self::TOTAL_ORDERS;
        $createdOrdersCount = 0;
        $skippedOrdersCount = 0;
        $totalArticlesCount = 0;

        $orderRepository = $this->em->getRepository(Order::class);

        // Process each seed order configuration.
        for ($i = 1; $i <= $totalOrders; $i++) {
            $faker->seed(12345 + $i);

            // Resolve order hash (first order uses predefined hash, subsequent orders are generated).
            $hash = $i === 1 ? self::PREDEFINED_HASH : $faker->md5();

            // Duplicate check: verify if an order with this exact hash already exists in database.
            $existingOrder = $orderRepository->findOneBy(['hash' => $hash]);

            if ($existingOrder !== null) {
                $io->warning(sprintf('Skipped Order #%d: Hash "%s" already exists in database.', $i, $hash));
                $skippedOrdersCount++;
                continue;
            }

            // Resolve client contact parameters.
            // We apply default values to different orders for improved testing.
            $clientName       = $i === 2 ? self::CLIENT_NAME       : $faker->firstName();
            $clientSurname    = $i === 3 ? self::CLIENT_SURNAME    : $faker->lastName();
            $email            = $i === 4 ? self::CLIENT_EMAIL      : $faker->safeEmail();
            $description      = $i === 5 ? self::ORDER_DESCRIPTION : $faker->text();
            $predictableToken = $faker->sha256();
            $sid_currency = CurrencySid::randomSid();
            $sid_measure = MeasureSid::randomSid();

            // Build new Order entity.
            $order = new Order();
            $order->setName(sprintf('Seeded Order #%d', $i))
                ->setHash($hash)
                ->setToken($predictableToken)
                ->setClientName($clientName)
                ->setClientSurname($clientSurname)
                ->setEmail($email)
                ->setDescription($description)
                ->setStatus(StatusSid::randomId())
                ->setPayType(PayTypeSid::randomId())
                ->setVatType(VatTypeSid::randomId())
                ->setLocale(LocaleSid::randomSid())
                ->setCurrency($sid_currency)
                ->setMeasure($sid_measure)
                ->setStep(StepSid::randomId());

            // 5. Generate randomized order articles (1 to 10 items per order).
            $articlesCount = $faker->numberBetween(1, 10);
            $totalArticlesCount += $articlesCount;

            for ($j = 1; $j <= $articlesCount; $j++) {
                $article = new OrderArticle();
                $article->setArticleId(1000 + ($i * 10) + $j)
                    ->setAmount((float) $faker->numberBetween(5, 50))
                    ->setPrice($faker->randomFloat(2, 15, 99))
                    ->setCurrency($sid_currency)
                    ->setMeasure($sid_measure);

                $order->addArticle($article);
                $this->em->persist($article);
            }

            // 6. Persist order entity into Doctrine Unit of Work.
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

        // 7. Flush changes to database if new records were generated.
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
