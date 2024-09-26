<?php

namespace App\DataFixtures;

use App\Entity\Contributor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Initialisation d'un admin en back.
 */
class ContributorFixtures extends Fixture
{
    /**
     * Constructor.
     */
    public function __construct(private UserPasswordHasherInterface $passwordEncoder)
    {
    }

    /**
     * <@inheritDoc>.
     */
    public function load(ObjectManager $manager)
    {
        $contributor = new Contributor();
        $contributor->setEmail('zz_petrescue@yopmail.fr');
        $contributor->setPassword($this->passwordEncoder->hashPassword($contributor, 'contributorpass'));
        $contributor->setRole(Contributor::ROLE_SUPER_ADMIN);
        $contributor->setFullname('Super Administrateur');

        $manager->persist($contributor);

        $manager->flush();
    }
}
