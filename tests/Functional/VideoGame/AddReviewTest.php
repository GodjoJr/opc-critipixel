<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AddReviewTest extends FunctionalTestCase
{

    public function testUserNotLoggedInCannotSeeReviewForm(): void
    {
        $videoGame = $this->getEntityManager()
            ->getRepository(VideoGame::class)
            ->findOneBy([]);

        $crawler = $this->get('/' . $videoGame->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('form[name="review"]'));
    }


    public function testUserNotLoggedInSubmitReviewIsUnauthorized(): void
    {
        $videoGame = $this->getEntityManager()
            ->getRepository(VideoGame::class)
            ->findOneBy([]);

        $this->client->request(
            'POST',
            '/' . $videoGame->getSlug(),
            [
                'review' => [
                    'rating' => 5,
                    'comment' => 'Super jeu !',
                ],
            ]
        );

        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_UNPROCESSABLE_ENTITY], true)
        );
    }


    public function testLoggedInUserCanSubmitValidReview(): void
    {
        $this->login('user+0@email.com');

        $videoGame = $this->getEntityManager()
            ->getRepository(VideoGame::class)
            ->findOneBy([]);

        $crawler = $this->get('/' . $videoGame->getSlug());

        $this->assertCount(1, $crawler->filter('form[name="review"]'));

        $form = $crawler->filter('form[name="review"]')->form([
            'review[rating]' => 5,
            'review[comment]' => 'Un jeu fantastique !',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/' . $videoGame->getSlug());

        $savedReview = $this->getEntityManager()
            ->getRepository(Review::class)
            ->findOneBy([
                'videoGame' => $videoGame,
                'comment' => 'Un jeu fantastique !',
            ]);

        $this->assertNotNull($savedReview);
        $this->assertSame(5, $savedReview->getRating());

        $crawler = $this->client->followRedirect();
        $this->assertCount(0, $crawler->filter('form[name="review"]'));
    }

    public function testSubmitReviewWithInvalidRatingTriggersValidationError(): void
    {
        $this->login('user+1@email.com');

        $videoGame = $this->getEntityManager()
            ->getRepository(VideoGame::class)
            ->findOneBy([]);

        $crawler = $this->get('/' . $videoGame->getSlug());

        $form = $crawler->filter('form[name="review"]')->form();
        $form['review[rating]']->disableValidation();
        $form['review[rating]'] = '6';
        $form['review[comment]'] = 'Test note hors limite';

        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testInvalidReviewIsNotPersistedInDatabase(): void
    {
        $this->login('user+2@email.com');

        $videoGame = $this->getEntityManager()
            ->getRepository(VideoGame::class)
            ->findOneBy([]);

        $uniqueComment = 'Commentaire invalide test - ' . uniqid();

        $crawler = $this->get('/' . $videoGame->getSlug());

        $form = $crawler->filter('form[name="review"]')->form();
        $form['review[rating]']->disableValidation();
        $form['review[rating]'] = '6';
        $form['review[comment]'] = $uniqueComment;

        $this->client->submit($form);

        $savedReview = $this->getEntityManager()
            ->getRepository(Review::class)
            ->findOneBy([
                'videoGame' => $videoGame,
                'comment' => $uniqueComment,
            ]);

        $this->assertNull($savedReview);
    }
}