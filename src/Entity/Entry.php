<?php

namespace App\Entity;

use App\Repository\EntryMetaDataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;


#[ORM\Table(name: '`entries`')]
#[ORM\Entity(repositoryClass: EntryMetaDataRepository::class)]
class Entry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: UlidType::NAME, unique: true, name: 'uniq_link_id')]
    private ?Ulid $uniqLinkId = null;

    #[ORM\Column(length: 255, name: '`filename`', nullable: false)]
    private ?string $filename = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $contentType = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: false, options: ['unsigned' => true])]
    private ?int $size = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    /**
     * @var Collection<int, EntryChunk>
     */
    #[ORM\OneToMany(targetEntity: EntryChunk::class, mappedBy: 'entry')]
    private Collection $entryChunks;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    private ?GuestLink $guestLink = null;

    public function __construct()
    {
        $this->entryChunks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): static
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return Collection<int, EntryChunk>
     */
    public function getEntryChunks(): Collection
    {
        return $this->entryChunks;
    }

    public function addEntryChunk(EntryChunk $entryChunk): static
    {
        if (!$this->entryChunks->contains($entryChunk)) {
            $this->entryChunks->add($entryChunk);
            $entryChunk->setEntry($this);
        }

        return $this;
    }

    public function removeEntryChunk(EntryChunk $entryChunk): static
    {
        if ($this->entryChunks->removeElement($entryChunk)) {
            // set the owning side to null (unless already changed)
            if ($entryChunk->getEntry() === $this) {
                $entryChunk->setEntry(null);
            }
        }

        return $this;
    }

    public function getGuestLink(): ?GuestLink
    {
        return $this->guestLink;
    }

    public function setGuestLink(?GuestLink $guestLink): static
    {
        $this->guestLink = $guestLink;

        return $this;
    }

    public function getUniqLinkId(): ?Ulid
    {
        return $this->uniqLinkId;
    }

    public function setUniqLinkId(?Ulid $uniqLinkId): static
    {
        $this->uniqLinkId = $uniqLinkId;

        return $this;
    }

}
