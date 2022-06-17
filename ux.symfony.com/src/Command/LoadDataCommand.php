<?php

namespace App\Command;

use App\Entity\Chat;
use App\Entity\Food;
use App\Entity\TodoItem;
use App\Entity\TodoList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:load-data', description: 'Resets and loads all the data needed in the database')]
class LoadDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->clearEntity(Chat::class, $io);
        $this->clearEntity(TodoItem::class, $io);
        $this->clearEntity(TodoList::class, $io);
        $this->growFood($io);

        return Command::SUCCESS;
    }

    private function growFood(SymfonyStyle $io): void
    {
        $foods = [
            'Banana 🍌',
            'Apple 🍎',
            'Hamburger 🍔',
            'Watermelon 🍉',
            'Cheese 🧀',
            'Pizza 🍕',
            'Pretzel 🥨',
            'Donut 🍩',
            'Pineapple 🍍',
            'Popcorn 🍿',
            'Egg 🍳',
            'Taco 🌮',
            'Ice Cream 🍦',
            'Cookie 🍪',
        ];

        $this->clearEntity(Food::class, $io);
        $io->info('Growing food...');
        foreach ($foods as $food) {
            $entity = new Food();
            $entity->setName($food);
            $entity->setVotes(rand(0, 100));
            $io->write([$food, ' ']);

            $this->entityManager->persist($entity);
        }
        $io->writeln('');

        $this->entityManager->flush();
    }

    private function clearEntity(string $className, SymfonyStyle $io): void
    {
        $io->writeln(sprintf('Clearing <comment>%s</comment>', $className));
        $this->entityManager
            ->createQuery('DELETE FROM '.$className)
            ->execute();
    }
}
