<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Classification;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\Count;
use Doctrine\ORM\Mapping as ORM;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class ClassificationEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Title $title,
        Classification $content,

        Model $model,
        ?RequestParams $request = null,
        ?Count $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $model,
            $request,
            $cost,
            $visibility
        );

        $this->title = $title;
        $this->content = new Content(json_encode($content));
    }

    public function getContent(): Classification
    {
        $classification = json_decode($this->content->value, true);

        $flags = [];
        foreach ($classification['categories'] as $flag => $val) {
            if ($val) {
                $flags[] = $flag;
            }
        }

        return new Classification($flags);
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
}
