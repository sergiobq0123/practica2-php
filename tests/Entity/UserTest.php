<?php

/**
 * @category TestEntities
 * @package  App\Tests\Entity
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://miw.etsisi.upm.es/ E.T.S. de Ingeniería de Sistemas Informáticos
 */

namespace App\Tests\Entity;

use App\Entity\User;
use Exception;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use PHPUnit\Framework\TestCase;

/**
 * Class UsuarioTest
 *
 * @package App\Tests\Entity
 *
 * @group   entities
 * @coversDefaultClass \App\Entity\User
 */
class UserTest extends TestCase
{
    protected static User $usuario;

    private static FakerGeneratorAlias $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$usuario = new User();
        self::$faker = FakerFactoryAlias::create('es_ES');
    }

    /**
     * Implement testConstructor().
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $usuario = new User('test@example.com', 'password123');
        self::assertSame('test@example.com', $usuario->getUserIdentifier());
        self::assertSame('test@example.com', $usuario->getEmail());
        self::assertNotEmpty($usuario->getRoles());
    }

    /**
     * Implement testGetId().
     *
     * @return void
     */
    public function testGetId(): void
    {
        self::assertSame(0, self::$usuario->getId());
    }

    /**
     * Implements testGetSetEmail().
     *
     * @throws Exception
     * @return void
     */
    public function testGetSetEmail(): void
    {
        $userEmail = self::$faker->email();
        self::$usuario->setEmail($userEmail);
        self::assertSame(
            $userEmail,
            self::$usuario->getEmail()
        );
    }

    /**
     * Implements testGetSetPassword().
     *
     * @return void
     * @throws Exception
     */
    public function testGetSetPassword(): void
    {
        $password = self::$faker->password(20);
        self::$usuario->setPassword($password);
        self::assertSame(
            $password,
            self::$usuario->getPassword()
        );
    }

    /**
     * Implement testGetSetRoles().
     *
     * @return void
     */
    public function testGetSetRoles(): void
    {
        self::assertContains(
            'ROLE_USER',
            self::$usuario->getRoles()
        );
        $role = self::$faker->word();
        self::$usuario->setRoles([ $role ]);
        self::assertContains(
            $role,
            self::$usuario->getRoles()
        );
    }
}
