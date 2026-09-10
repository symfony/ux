<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 120)]
    public string $name = '';

    #[ORM\Column(length: 180)]
    public string $email = '';

    #[ORM\Column(length: 40)]
    public string $phone = '';
}
