<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class CountRatingsPerValueTest extends TestCase
{
    private RatingHandler $ratingHandler;

    protected function setUp(): void
    {
        $this->ratingHandler = new RatingHandler();
    }


    public function testCountRatingsPerValueWithNoReviews(): void
    {
       
        $videoGame = new VideoGame();

    
        $this->ratingHandler->countRatingsPerValue($videoGame);

 
        $counts = $videoGame->getNumberOfRatingsPerValue();

        $this->assertEquals(0, $counts->getNumberOfOne());
        $this->assertEquals(0, $counts->getNumberOfTwo());
        $this->assertEquals(0, $counts->getNumberOfThree());
        $this->assertEquals(0, $counts->getNumberOfFour());
        $this->assertEquals(0, $counts->getNumberOfFive());
    }


    public function testCountRatingsPerValueWithVariedRatings(): void
    {
        
        $videoGame = new VideoGame();

        $ratings = [1, 1, 3, 5, 5, 5];

        foreach ($ratings as $rating) {
            $review = (new Review())->setRating($rating);
            $videoGame->getReviews()->add($review);
        }
    
        $this->ratingHandler->countRatingsPerValue($videoGame);
  
        $counts = $videoGame->getNumberOfRatingsPerValue();

        $this->assertEquals(2, $counts->getNumberOfOne(), 'Le nombre de notes "1" doit être égal à 2');
        $this->assertEquals(0, $counts->getNumberOfTwo(), 'Le nombre de notes "2" doit être égal à 0');
        $this->assertEquals(1, $counts->getNumberOfThree(), 'Le nombre de notes "3" doit être égal à 1');
        $this->assertEquals(0, $counts->getNumberOfFour(), 'Le nombre de notes "4" doit être égal à 0');
        $this->assertEquals(3, $counts->getNumberOfFive(), 'Le nombre de notes "5" doit être égal à 3');
    }
}