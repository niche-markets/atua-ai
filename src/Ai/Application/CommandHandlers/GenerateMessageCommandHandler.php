<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\GenerateMessageCommand;
use Ai\Domain\Completion\MessageServiceInterface;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Token;
use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\ValueObjects\Count;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;
use File\Domain\Entities\ImageFileEntity;
use File\Domain\ValueObjects\BlurHash;
use File\Domain\ValueObjects\Height;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use File\Domain\ValueObjects\Width;
use GdImage;
use Generator;
use kornrunner\Blurhash\Blurhash as BlurhashHelper;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\FileSystem\CdnInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class GenerateMessageCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private WorkspaceRepositoryInterface $wsRepo,
        private LibraryItemRepositoryInterface $repo,
        private AssistantRepositoryInterface $aRepo,

        private AiServiceFactoryInterface $factory,
        private EventDispatcherInterface $dispatcher,
        private CdnInterface $cdn,
    ) {
    }

    /**
     * @return Generator<int,Chunk|MessageEntity>
     * @throws WorkspaceNotFoundException
     * @throws UserNotFoundException
     * @throws LibraryItemNotFoundException
     * @throws InsufficientCreditsException
     */
    public function handle(GenerateMessageCommand $cmd): Generator
    {
        $ws = $cmd->workspace instanceof WorkspaceEntity
            ? $cmd->workspace
            : $this->wsRepo->ofId($cmd->workspace);

        $user = $cmd->user instanceof UserEntity
            ? $cmd->user
            : $this->userRepo->ofId($cmd->user);

        // Find the conversation
        $conversation = $cmd->conversation instanceof ConversationEntity
            ? $cmd->conversation
            : $this->repo->ofId($cmd->conversation);

        if (!($conversation instanceof ConversationEntity)) {
            throw new LibraryItemNotFoundException(
                $cmd->conversation instanceof ConversationEntity
                    ? $cmd->conversation->getId() : $cmd->conversation
            );
        }

        $service = $this->factory->create(
            MessageServiceInterface::class,
            $cmd->model
        );

        if (
            !is_null($ws->getTotalCreditCount()->value)
            && (float) $ws->getTotalCreditCount()->value <= 0
        ) {
            throw new InsufficientCreditsException();
        }

        $parent = $cmd->parent ? $conversation->findMessage($cmd->parent) : null;
        $assistant = null;

        if ($cmd->assistant) {
            $assistant = $cmd->assistant instanceof AssistantEntity
                ? $cmd->assistant
                : $this->aRepo->ofId($cmd->assistant);
        }

        $image = null;
        if ($cmd->file) {
            $ext = pathinfo($cmd->file->getClientFilename(), PATHINFO_EXTENSION);

            // Save file to CDN
            $stream = $cmd->file->getStream();
            $stream->rewind();
            $name = Uuid::uuid4()->toString() . '.' . $ext;
            $this->cdn->write("/" . $name, $stream->getContents());

            $stream->rewind();

            $gdimg = imagecreatefromstring($stream->getContents());
            $width = imagesx($gdimg);
            $height = imagesy($gdimg);

            $image = new ImageFileEntity(
                new Storage($this->cdn->getAdapterLookupKey()),
                new ObjectKey($name),
                new Url($this->cdn->getUrl($name)),
                new Size($cmd->file->getSize()),
                new Width($width),
                new Height($height),
                new BlurHash($this->generateBlurHash($gdimg, $width, $height)),
            );
        }

        if ($cmd->prompt) {
            $message = MessageEntity::userMessage(
                $conversation,
                $cmd->prompt,
                $user,
                $cmd->model,
                $parent,
                $assistant,
                $cmd->quote,
                $image
            );

            yield $message;
        } elseif ($cmd->parent) {
            $message = $conversation->findMessage($cmd->parent);
            $assistant = $message->getAssistant();
        } else {
            throw new \InvalidArgumentException('Prompt or parent message is required');
        }

        yield new Chunk(); // Placeholder for assistant message
        $resp = $service->generateMessage(
            $cmd->model,
            $message
        );

        $content = '';
        foreach ($resp as $chunk) {
            if ($chunk->data instanceof Token) {
                $content .= (string) $chunk->data;
            }

            yield $chunk;
        }

        /** @var Count */
        $cost = $resp->getReturn();

        $entity = MessageEntity::assistantMessage(
            $conversation,
            new Content($content),
            $message,
            $cost,
            $cmd->model,
            $assistant
        );

        if (!$conversation->getTitle()->value) {
            $service = $this->factory->create(
                TitleServiceInterface::class,
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $content = '';
            $i = 0;
            foreach ($conversation->getMessages() as $msg) {
                $content .= $msg->getContent()->value;
                $i++;

                if ($i >= 2) {
                    break;
                }
            }

            $content = new Content($content);
            $titleResp = $service->generateTitle(
                $content,
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $conversation->setTitle($titleResp->title);
            $cost = new Count($cost->value + $titleResp->cost->value);
        }

        // Deduct credit from workspace
        $ws->deductCredit($cost);

        // Dispatch event
        $event = new CreditUsageEvent($ws, $cost);
        $this->dispatcher->dispatch($event);

        yield $entity;
    }

    private function generateBlurHash(GdImage $image, int $width, int $height): string
    {
        if ($width > 64) {
            $height = (int) (64 / $width * $height);
            $width = 64;
            $image = imagescale($image, $width);
        }

        $pixels = [];
        for ($y = 0; $y < $height; ++$y) {
            $row = [];
            for ($x = 0; $x < $width; ++$x) {
                $index = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $index);

                $row[] = [$colors['red'], $colors['green'], $colors['blue']];
            }
            $pixels[] = $row;
        }

        $components_x = 4;
        $components_y = 3;
        return BlurhashHelper::encode($pixels, $components_x, $components_y);
    }
}
