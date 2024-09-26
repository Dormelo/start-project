<?php

// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->getUserData();
        $startingIndex = 1;

        foreach ($users as $key => $userData) {
            $key = $startingIndex++;
            $user = $this->createUser($userData, $key);
            $this->applyRoles($user, $userData['roles']);
            $this->applyAddress($user, $userData['address'] ?? null);

            $manager->persist($user);
            $this->addReference("user-{$key}", $user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AddressFixtures::class,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUserData(): array
    {
        return [
            // Adoptant with ROLE_ADOPTANT
            [
                'email' => 'adoptant@example.com',
                'roles' => [UserRoleEnum::ADOPTANT->value],
                'typology' => 'individual',
                'address' => 'address-1',
                'civilite' => 'man',
                'isVerified' => false
            ],
            // Adoptant Verified with ROLE_ADOPTANT, ROLE_VERIFIED
            [
                'email' => 'adoptant_verified@example.com',
                'roles' => [
                    UserRoleEnum::ADOPTANT->value
                ],
                'typology' => 'individual',
                'address' => 'address-2',
                'civilite' => 'woman',
                'isVerified' => true
            ],
            // Proposant with ROLE_PROPOSANT
            [
                'email' => 'proposant@example.com',
                'roles' => [UserRoleEnum::PROPOSANT->value],
                'typology' => 'association',
                'structureName' => 'Association1',
                'rna' => 'W123456789',
                'address' => 'address-3',
                'civilite' => 'woman',
                'isVerified' => false
            ],
            // Proposant Verified with ROLE_PROPOSANT, ROLE_VERIFIED
            [
                'email' => 'proposant_verified@example.com',
                'roles' => [
                    UserRoleEnum::PROPOSANT->value
                ],
                'typology' => 'individual',
                'address' => 'address-4',
                'civilite' => 'woman',
                'isVerified' => true
            ],
            // Proposant Verified Documents Certified with ROLE_PROPOSANT, ROLE_VERIFIED, ROLE_DOCUMENTS_CERTIFIED
            [
                'email' => 'proposant_verified_certified@example.com',
                'roles' => [
                    UserRoleEnum::PROPOSANT->value
                ],
                'typology' => 'individual',
                'address' => 'address-5',
                'civilite' => 'woman',
                'isVerified' => true
            ],
            // Admin
            [
                'email' => 'admin@example.com',
                'roles' => [
                    UserRoleEnum::ADMIN->value
                ],
                'typology' => 'individual',
                'address' => 'address-10',
                'civilite' => 'woman',
                'isVerified' => true
            ],
            
        ];
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function createUser(array $userData, int $key): User
    {
        $user = new User();
        $user->setEmail($userData['email'])
            ->setFirstname("User{$key}Firstname")
            ->setLastname("User{$key}Lastname")
            ->setPhone("069900990{$key}")
            ->setBirthdate(new \DateTime("199{$key}-01-01"))
            ->setPassword($this->passwordHasher->hashPassword($user, "password{$key}"))
            ->setCivilite($userData['civilite'] ?? 'woman')
            ->setVerified($userData['isVerified']);

        if (isset($userData['typology'])) {
            $user->setTypology($userData['typology']);
            if (isset($userData['structureName'])) {
                $user->setStructureName($userData['structureName']);
            }
            if (isset($userData['siret'])) {
                $user->setSiret($userData['siret']);
            }
            if (isset($userData['rna'])) {
                $user->setRNA($userData['rna']);
            }
        }

        return $user;
    }

    /**
     * @param User $user
     * @param string[] $roles
     */
    private function applyRoles(User $user, array $roles): void
    {
        foreach ($roles as $role) {
            $user->addRole($role);
        }
    }

    /**
     * @param User $user
     * @param string|null $addressReference
     *
     * @return void
     */
    private function applyAddress(User $user, ?string $addressReference): void
    {
        if ($addressReference && $this->hasReference($addressReference)) {
            $address = $this->getReference($addressReference);
            $user->setAddress($address);
        }
    }

}
