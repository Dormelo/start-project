<?php

namespace App\DataFixtures;

use App\Entity\Image;
use App\Entity\Rescue;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentMomentEnum;
use App\Enum\RescueStatusEnum;
use App\Service\FileUploadManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RescueFixtures extends Fixture implements DependentFixtureInterface
{
    private string $projectDir;
    private FileUploadManager $fileUploadManager;

    public function __construct(string $projectDir, FileUploadManager $fileUploadManager)
    {
        $this->projectDir = $projectDir;
        $this->fileUploadManager = $fileUploadManager;
    }

    public function load(ObjectManager $manager): void
    {

        foreach ($this->getData() as $i => $data) {
            $user = $this->getHasReference($data['user']);
            $association = $this->getHasReference($data["association"]);

            if ($user && $association && $association->isCertified()) {
                ++$i;
                $image = $this->createImage($i);

                $rescue = new Rescue();
                $rescue->setName("Campagne d'adoption asso {$i}")
                    ->setAssociation($association)
                    ->setCreatedBy($user)
                    ->setImage($image)
                    ->setStartDate(new \DateTime())
                    ->setEndDate((new \DateTime())->modify('+180 days'))
                    ->setPriceInEuros(rand(5, 20))
                    ->setPaymentMethods([
                        PaymentMethodEnum::CREDIT_CARD->value,
                        PaymentMethodEnum::BANK_TRANSFERT->value,
                        PaymentMethodEnum::HELLOASSO->value,
                    ])
                    ->setHenOrigin("Origine {$i}")
                    ->setHenRace("Race {$i}")
                    ->setHenDescription("Description pour la race {$i}")
                    ->setHenQuantity(rand(100, 200))
                    ->setHenLimitPerClient(rand(1, 20))
                    ->setStatus(RescueStatusEnum::STATUS_ONGOING->value)
                    ->setPaymentMoment(PaymentMomentEnum::ADVANCE_PAYMENT->value)
                    ->setLimitRefundDate((new \DateTime())->modify('+180 days'));

                $manager->persist($rescue);
                $manager->flush();
                $this->addReference('rescue-'.$i , $rescue);
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            AssociationFixtures::class,
            UserFixtures::class,
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
                'user' => 'user-5',
                'association' => 'association-3'
            ],
        ];
    }

    private function createImage($i): Image
    {
        // Upload de l'image
        $imageName = "fixture_rescue{$i}.webp";
        $imagePath = $this->projectDir.'/public/fixtures/images/'.$imageName;

        $temporaryImage = tempnam(sys_get_temp_dir(), 'upl'); // Création d'un fichier temporaire
        copy($imagePath, $temporaryImage);

        $file = new UploadedFile(
            $temporaryImage,
            $imageName,
            null,
            null,
            true // Marque le fichier comme "test", ce qui évite certaines validations
        );

        return $this->fileUploadManager->createImage('rescues', $file);
    }
}
