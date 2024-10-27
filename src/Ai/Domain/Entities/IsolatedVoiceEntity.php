<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\Count;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\FileEntity;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class IsolatedVoiceEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'input_file_id')]
    private FileEntity $inputFile;

    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'output_file_id')]
    private FileEntity $outputFile;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        FileEntity $inputFile,
        FileEntity $outputFile,
        Title $title,

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
        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile;
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

    public function getInputFile(): FileEntity
    {
        return $this->inputFile;
    }

    public function getOutputFile(): FileEntity
    {
        return $this->outputFile;
    }
}
