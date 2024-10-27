<?php

declare(strict_types=1);

namespace Assistant\Domain\Entities;

use Assistant\Domain\ValueObjects\Description;
use Ai\Domain\ValueObjects\Instructions;
use Assistant\Domain\ValueObjects\Name;
use Assistant\Domain\ValueObjects\Status;
use Assistant\Domain\ValueObjects\AvatarUrl;
use Assistant\Domain\ValueObjects\Expertise;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;

#[ORM\Entity]
#[ORM\Table(name: 'assistant')]
#[ORM\HasLifecycleCallbacks]
class AssistantEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Name::class, columnPrefix: false)]
    private Name $name;

    #[ORM\Embedded(class: Expertise::class, columnPrefix: false)]
    private Expertise $expertise;

    #[ORM\Embedded(class: Description::class, columnPrefix: false)]
    private Description $description;

    #[ORM\Embedded(class: Instructions::class, columnPrefix: false)]
    private Instructions $instructions;

    #[ORM\Embedded(class: AvatarUrl::class, columnPrefix: false)]
    private AvatarUrl $avatar;

    #[ORM\Column(type: Types::SMALLINT, enumType: Status::class, name: 'status')]
    private Status $status;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct(Name $name)
    {
        $this->id = new Id();
        $this->name = $name;
        $this->expertise = new Expertise();
        $this->description = new Description();
        $this->instructions = new Instructions();
        $this->avatar = new AvatarUrl();
        $this->status = Status::ACTIVE;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getExpertise(): Expertise
    {
        return $this->expertise;
    }

    public function setExpertise(Expertise $expertise): self
    {
        $this->expertise = $expertise;
        return $this;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(Description $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getInstructions(): Instructions
    {
        return $this->instructions;
    }

    public function setInstructions(Instructions $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getAvatar(): AvatarUrl
    {
        return $this->avatar;
    }

    public function setAvatar(AvatarUrl $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
