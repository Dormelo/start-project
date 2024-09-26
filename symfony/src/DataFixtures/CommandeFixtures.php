<?php

namespace App\DataFixtures;

use App\Entity\Commande;
use App\Entity\Rescue;
use App\Entity\User;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CommandeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getCommandData() as $i => $commandData) {
            $user = $this->getHasReference($commandData['user']);
            $rescue  = $this->getHasReference($commandData['rescue']);

            if($user && $rescue) {
                $commande = new Commande();
                $commande->setUser($user)
                    ->setRescue($rescue)
                    ->setReference('CMD-'.++$i)
                    ->setQuantity(random_int(1, 3))
                    ->setOrderStatus(OrderStatusEnum::STATUS_PAID->value)
                    ->setPaymentMethod(PaymentMethodEnum::CREDIT_CARD->value)
                    ->setPaymentDate(new \DateTime())
                    ->setRefunded(false);
    
                $manager->persist($commande);
                
            }  
        }
        $manager->flush();  
    
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            RescueFixtures::class,
        ];
    }

    private function getHasReference($data)
    {
        return $this->hasReference($data)
            ? $this->getReference($data) : null;
    }

    private function getCommandData(): array
    {
        return [
            // Pour utilisateur 'user-2' adoptant verified
            [
                'user' => 'user-2',
                'rescue' => 'rescue-1'
            ]    
        ];
    }
}
