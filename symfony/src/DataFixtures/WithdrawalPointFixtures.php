<?php

namespace App\DataFixtures;

use App\Entity\WithdrawalPoint;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WithdrawalPointFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as $i =>$data) {
            $association = $this->getHasReference($data['association']);
            if (!$association) {
                continue;
            }

            $withdrawalPoint = new WithdrawalPoint();
            $withdrawalPoint
                ->setName('withdrawalPoint'.++$i)
                ->setAssociation($association);

            if ($address = $this->getHasReference($data['address'])) {
                $withdrawalPoint->setAddress($address);
            }
    
            $manager->persist($withdrawalPoint);
        }
        
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AssociationFixtures::class,
            AddressFixtures::class,
        ];
    }

    private function getHasReference($data)
    {
        return $this->hasReference($data)
            ? $this->getReference($data) : null;
    }

    private function getData(): array
    {
        return [
            [
                'association' => 'association-4',
                'address' => 'address-6'
            ],
            [
                'association' => 'association-5',
                'address' => 'address-7'
            ],
        ];
    }
}
