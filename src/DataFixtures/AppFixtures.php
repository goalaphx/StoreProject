<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;

    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $plainPassword = "admin";
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setUsername("admin");
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setEmail("mehdiidlehcen123@gmail.com");
        $user->setProfilepic("nothing");
        $user->setPhonenum("0689488850");
        $manager->persist($user);

        $manager->flush();
    }
}
