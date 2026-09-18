<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Tag;
use App\Tests\Functional\FunctionalTestCase;

final class FilterTest extends FunctionalTestCase
{

    public static function provideTagCases(): iterable
    {
        yield 'aucun tag' => [
            'tagNames' => [],
            'expectedCount' => 10,
        ];

        yield 'un seul tag' => [
            'tagNames' => ['Action'],
            'expectedCount' => 9,
        ];

        yield 'deux tags' => [
            'tagNames' => ['Action', 'Aventure'],
            'expectedCount' => 3,
        ];

        yield 'cinq tags' => [
            'tagNames' => ['Action', 'Aventure', 'RPG', 'Stratégie', 'Sport'],
            'expectedCount' => 0,
        ];
    }

    /**
     * @dataProvider provideTagCases
     * @param string[] $tagNames
     */
    public function testShouldFilterVideoGamesByTags(array $tagNames, int $expectedCount): void
    {
        $query = [];

        if (!empty($tagNames)) {
            $tags = $this->getEntityManager()
                ->getRepository(Tag::class)
                ->findBy(['name' => $tagNames]);

            $query = [
                'filter' => [
                    'tags' => array_map(static fn(Tag $tag) => (string) $tag->getId(), $tags),
                ],
            ];
        }

        $this->get('/', $query);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount($expectedCount, 'article.game-card');
    }

    public function testShouldHandleNonExistentTag(): void
    {
        $this->get('/', [
            'filter' => [
                'tags' => ['999999'],
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
    }

    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        $this->get('/', ['page' => 2]);
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
    }

    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }
}