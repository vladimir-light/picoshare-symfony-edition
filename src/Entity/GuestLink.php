<?php

namespace App\Entity;

use App\Repository\GuestLinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: '`guest_links`')]
#[ORM\Entity(repositoryClass: GuestLinkRepository::class)]
class GuestLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'uniq_link_id', type: UlidType::NAME, unique: true)]
    private ?Ulid $uniqLinkId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxFileBytes = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxUploads = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileExpiration = null;

    #[ORM\Column]
    private ?bool $disabled = null;

    /**
     * @var Collection<int, Entry>
     */
    #[ORM\OneToMany(targetEntity: Entry::class, mappedBy: 'guestLink')]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

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

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getMaxFileBytes(): ?int
    {
        return $this->maxFileBytes;
    }

    public function setMaxFileBytes(?int $maxFileBytes): static
    {
        $this->maxFileBytes = $maxFileBytes;

        return $this;
    }

    public function getMaxUploads(): ?int
    {
        return $this->maxUploads;
    }

    public function setMaxUploads(?int $maxUploads): static
    {
        $this->maxUploads = $maxUploads;

        return $this;
    }

    public function getFileExpiration(): ?string
    {
        return $this->fileExpiration;
    }

    public function setFileExpiration(?string $fileExpiration): static
    {
        $this->fileExpiration = $fileExpiration;

        return $this;
    }

    public function isDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @return Collection<int, Entry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(Entry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setGuestLink($this);
        }

        return $this;
    }

    public function removeEntry(Entry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            // set the owning side to null (unless already changed)
            if ($entry->getGuestLink() === $this) {
                $entry->setGuestLink(null);
            }
        }

        return $this;
    }
}
