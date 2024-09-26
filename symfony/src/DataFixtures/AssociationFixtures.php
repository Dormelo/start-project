<?php

// src/DataFixtures/AssociationFixtures.php

namespace App\DataFixtures;

use App\Entity\Association;
use App\Entity\ProofDocument;
use App\Service\FileUploadManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AssociationFixtures extends Fixture implements DependentFixtureInterface
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
        foreach ($this->getAssociationData() as $i => $associationData) {
            if ($this->hasReference($associationData['user'])) {
                $user = $this->getReference($associationData['user']);
                ++$i;
                $association = new Association();
                $association->setName("Association {$i}")
                    ->setEmail("association{$i}@example.com")
                    ->setPhone("069900990{$i}")
                    ->setRNA("W12345678{$i}")
                    ->setOwner($user)
                    ->addMember($user)
                    ->setDescription("Description pour Association {$i}")
                    ->setSiret("3625218790003{$i}")
                    ->addProofDocument($this->createProofDocument($i))
                    ->setCertified($associationData['isCertified']);

                    $manager->persist($association);
                    $manager->flush();
                    $this->addReference("association-{$i}", $association);
            };
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    private function getAssociationData(): array
    {
        
        return [
            // 'user-3'  avec le rôle ROLE_PROPOSANT
            [
                'user' => 'user-3',
                'isCertified' => false
            ],
            // 'user-4'  avec le rôle ROLE_PROPOSANT, ROLE_VERIFIED
            [
                'user' => 'user-4',
                'isCertified' => false
            ],
            // 'user-5'  avec le rôle ROLE_PROPOSANT, ROLE_VERIFIED, ROLE_DOCUMENTS_CERTIFIED
            [
                'user' => 'user-5',
                'isCertified' => true
            ],
        ];
    }

    private function createProofDocument($i): ProofDocument
    {
        // Upload du proof document
        $imageName = "proof_document_{$i}.pdf";
        $imagePath = $this->projectDir.'/public/fixtures/images/proof_document.pdf';

        $temporaryImage = tempnam(sys_get_temp_dir(), 'upl'); // Création d'un fichier temporaire
        copy($imagePath, $temporaryImage);

        $file = new UploadedFile(
            $temporaryImage,
            $imageName,
            null,
            null,
            true // Marque le fichier comme "test", ce qui évite certaines validations
        );

        return $this->fileUploadManager->createProofDocument('proof_documents', $file);
    }
}
