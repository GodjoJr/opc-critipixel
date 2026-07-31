<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class AverageRatingTest extends TestCase
{
    private RatingHandler $ratingHandler;

    protected function setUp(): void
    {
        $this->ratingHandler = new RatingHandler();
    }

    public function testCalculateAverageWithNoReviewsReturnsNull(): void
    {
        $videoGame = new VideoGame();

        $this->ratingHandler->calculateAverage($videoGame);

        $this->assertNull($videoGame->getAverageRating());
    }


    public function testCalculateAverageWithExactAverage(): void
    {
    
        $videoGame = new VideoGame();

        $review1 = (new Review())->setRating(3);
        $review2 = (new Review())->setRating(5);

        $videoGame->getReviews()->add($review1);
        $videoGame->getReviews()->add($review2);

        $this->ratingHandler->calculateAverage($videoGame);

        $this->assertSame(4, $videoGame->getAverageRating());
    }


    public function testCalculateAverageRoundsUpWithCeil(): void
    {
      
        $videoGame = new VideoGame();

        $review1 = (new Review())->setRating(2);
        $review2 = (new Review())->setRating(3);

        $videoGame->getReviews()->add($review1);
        $videoGame->getReviews()->add($review2);

   
        $this->ratingHandler->calculateAverage($videoGame);

        $this->assertSame(3, $videoGame->getAverageRating());
    }
}