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

    #[ORM\Column(name: 'data_chunk', type: Types::BLOB)]
    private $dataChunk;

    #[ORM\Column(name: 'data_chunk_size', type: Types::INTEGER, nullable: false, options: ['default' => 0, 'unsigned' => true])]
    private ?int $dataChunkSize = 0;

    #[ORM\Column(name: 'data_chunk_index', type: Types::SMALLINT)]
    private ?int $dataChunkIndex = 0;

    #[ORM\ManyToOne(fetch: 'EXTRA_LAZY', inversedBy: 'entryChunks')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, fieldName: 'entry_id')]
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

    public function getDataChunkSize(): ?int
    {
        return $this->dataChunkSize;
    }

    public function setDataChunkSize(?int $dataChunkSize): static
    {
        $this->dataChunkSize = $dataChunkSize;
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
