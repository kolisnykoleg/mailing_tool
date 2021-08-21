<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';
    protected static $defaultDescription = 'Creates a new user';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserPasswordHasherInterface
     */
    private $passwordHasher;


    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        string $name = null
    ) {
        parent::__construct($name);
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        do {
            $username = $io->ask('Username');
            $userExists = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['username' => $username]);
            if ($userExists) {
                $io->error('Username is already in use');
            }
        } while ($userExists);

        $password = $io->ask('Password');

        $role = $io->askQuestion(
            new ChoiceQuestion('Role', [
                'ROLE_ADMIN',
                'ROLE_USER',
            ])
        );

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles([$role]);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User created successfully');

        return Command::SUCCESS;
    }
}
