<?php

namespace App\Entity;

use App\Repository\EntryChunkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: '`entry_chunks`')]
#[ORM\Entity(repositoryClass: EntryChunkRepository::class)]
class EntryChunk
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $dataChunkIndex = null;

    #[ORM\Column(type: Types::BLOB)]
    private $dataChunk;

    #[ORM\ManyToOne(inversedBy: 'entryChunks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entry $entry = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataChunkIndex(): ?int
    {
        return $this->dataChunkIndex;
    }

    public function setDataChunkIndex(int $dataChunkIndex): static
    {
        $this->dataChunkIndex = $dataChunkIndex;

        return $this;
    }

    public function getDataChunk()
    {
        return $this->dataChunk;
    }

    public function setDataChunk($dataChunk): static
    {
        $this->dataChunk = $dataChunk;

        return $this;
    }

    public function getEntry(): ?Entry
    {
        return $this->entry;
    }

    public function setEntry(?Entry $entry): static
    {
        $this->entry = $entry;

        return $this;
    }
}
