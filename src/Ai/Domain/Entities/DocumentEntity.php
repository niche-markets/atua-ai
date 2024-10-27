<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\Count;
use Doctrine\ORM\Mapping as ORM;
use Preset\Domain\Entities\PresetEntity;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class DocumentEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content;

    #[ORM\ManyToOne(targetEntity: PresetEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?PresetEntity $preset = null;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,

        Title $title,
        ?PresetEntity $preset = null,

        ?RequestParams $requestParams = null,
        ?Count $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $model,
            $requestParams,
            $cost,
            $visibility
        );

        $this->title = $title;
        $this->content = new Content();
        $this->preset = $preset;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(Title $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function setContent(Content $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPreset(): ?PresetEntity
    {
        return $this->preset;
    }
}
