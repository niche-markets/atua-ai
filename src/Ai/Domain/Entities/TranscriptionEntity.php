<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Transcription;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\Count;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\FileEntity;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class TranscriptionEntity extends AbstractLibraryItemEntity
{
    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'input_file_id')]
    private FileEntity $inputFile;

    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        FileEntity $inputFile,
        Title $title,
        Transcription $content,

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

        $this->inputFile = $inputFile;
        $this->title = $title;
        $this->content = new Content(json_encode($content));
    }

    public function getInputFile(): FileEntity
    {
        return $this->inputFile;
    }

    public function getContent(): Transcription
    {
        $transcription = json_decode($this->content->value, true);

        return new Transcription(
            $transcription['text'],
            $transcription['language'],
            $transcription['duration'],
            $transcription['segments'],
            $transcription['words'],
        );
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
