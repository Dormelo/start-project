<?php

namespace App\DataFixtures;

use App\Entity\Address;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AddressFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i = 1; $i <= 10; ++$i) {
            $address = new Address();
            $address->setStreet($faker->streetAddress())
                ->setZipcode($faker->postcode())
                ->setCity($faker->city())
                ->setRegion($faker->region())
                ->setDepartment($faker->departmentNumber())
                ->setCountry($faker->country())
                ->setLatitude($faker->latitude())
                ->setLongitude($faker->longitude());
            $manager->persist($address);
            $manager->flush();
            $this->addReference('address-'.$i, $address);
        }

        
    }
}
