<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_fill_callback;
use function array_map;
use function array_walk;
use function count;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    private const TAG_NAMES = [
        'Action',
        'Aventure',
        'RPG',
        'Stratégie',
        'Sport',
    ];

    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();

        $tags = array_map(
            static fn (string $name): Tag => (new Tag())->setName($name),
            self::TAG_NAMES
        );

        array_walk($tags, [$manager, 'persist']);

        $videoGames = array_fill_callback(0, 50, fn (int $index): VideoGame => (new VideoGame)
            ->setTitle(sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true))
            ->setRating(($index % 5) + 1)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );


        foreach ($videoGames as $index => $videoGame) {
            $numberOfTags = $index % 3;

            for ($i = 0; $i < $numberOfTags; ++$i) {
                $videoGame->getTags()->add($tags[($index + $i) % count($tags)]);
            }
        }

        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();

        foreach ($videoGames as $index => $videoGame) {
            if ($index === 0) {
                continue;
            }


            for ($i = 0; $i < 3; ++$i) {
                $review = (new Review())
                    ->setVideoGame($videoGame)
                    ->setUser($users[($index + $i) % count($users)])
                    ->setRating((($index + $i) % 5) + 1)
                    ->setComment($i === 0 ? $this->faker->sentence() : null);


                $videoGame->getReviews()->add($review);

                $manager->persist($review);
            }

            $this->calculateAverageRating->calculateAverage($videoGame);
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}