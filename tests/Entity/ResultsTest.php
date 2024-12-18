<?php

namespace App\Tests\Entity;

use App\Entity\Results;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ResultsTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');

        $resultValue = 95.5;
        $time = new \DateTime('2024-12-16 12:00:00');
        $results = new Results($user, $resultValue, $time);

        // Act and Assert
        $this->assertSame($user, $results->getUser());
        $this->assertEquals($resultValue, $results->getResult());
        $this->assertEquals($time, $results->getTime());
    }

    public function testConstructorWithDefaults(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('default@example.com');
        $user->setPassword('password');

        $results = new Results($user);

        // Assert
        $this->assertSame($user, $results->getUser());
        $this->assertEquals(0.0, $results->getResult());
        $this->assertInstanceOf(\DateTime::class, $results->getTime());
    }
}