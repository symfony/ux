<?php

namespace App\Command;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:seed-clients', description: 'Create a small set of demo clients')]
final class SeedClientsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        for ($i = 1; $i <= 1000; $i++) {
            $client = new Client();
            $client->name = sprintf('Client %d', $i);
            $client->email = sprintf('client%03d@example.org', $i);
            $client->phone = sprintf('+1 555 010 %04d', $i);

            $this->entityManager->persist($client);

            if (0 === $i % 100) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $output->writeln('1000 demo clients created.');

        return Command::SUCCESS;
    }
}
