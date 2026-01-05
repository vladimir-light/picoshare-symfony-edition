<?php

namespace App\Entity;

use App\Repository\GuestLinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Doctrine\DBAL\Types\Types;

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
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    /*
     * \DateTime after Link is considered as expired and is no longer usable
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'This value should be >= 1')]
    private ?int $maxFileBytes = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'This value should be >= 1')]
    private ?int $maxUploads = null;

    #[ORM\Column(name: 'current_uploads', nullable: false, options: ['default' => 0])]
    private int $currentUploads = 0;

    /*
     * Default-Setting for Uploads-Expiration in upload form..
     * null -> never
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileExpiration = null;

    #[ORM\Column]
    private ?bool $disabled = false;

    /**
     * @var Collection<int, Entry>
     */
    #[ORM\OneToMany(targetEntity: Entry::class, mappedBy: 'guestLink')]
    private Collection $entries;

    public function __construct(?string $label = 'unnamed')
    {
        $this->label = $label;
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

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isEndless(): bool
    {
        return $this->expiresAt === null;
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

    public function getMaxFileSizeInMegaBytes(): int
    {
        return $this->getMaxFileBytes() / (1024 * 1024);
    }

    public function setMaxFileSizeInMegaBytes(int $mb): static
    {
        $this->setMaxFileBytes( $mb * 1024 * 1024 );

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

    public function getCurrentUploads(): int
    {
        return $this->currentUploads;
    }

    public function setCurrentUploads(int $currentUploads): static
    {
        $this->currentUploads = $currentUploads;

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

    public function isUnlimitedUploads(): bool
    {
        return $this->maxUploads === null;
    }

    public function isUnlimitedFileSize(): bool
    {
        return $this->maxFileBytes === null;
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

    public function isLinkExpired(\DateTimeInterface $nowRef): bool
    {
        return $nowRef->getTimestamp() > $this->getExpiresAt()->getTimestamp();
    }

    public function isExpired(\DateTimeImmutable $nowRef): bool
    {
        // no expiration date -> not expired :)
        if ($this->getExpiresAt() === null) {
            return false;
        }

        return $nowRef->getTimestamp() > $this->getExpiresAt()->getTimestamp();
    }
}
