<?php

declare(strict_types=1);

namespace Netgen\TagsBundle\Core\Event;

use Netgen\TagsBundle\API\Repository\Events\Tags as APIEvents;
use Netgen\TagsBundle\API\Repository\TagsService as TagsServiceInterface;
use Netgen\TagsBundle\API\Repository\Values\Tags\SearchResult;
use Netgen\TagsBundle\API\Repository\Values\Tags\SynonymCreateStruct;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Netgen\TagsBundle\API\Repository\Values\Tags\TagCreateStruct;
use Netgen\TagsBundle\API\Repository\Values\Tags\TagList;
use Netgen\TagsBundle\API\Repository\Values\Tags\TagUpdateStruct;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TagsService implements TagsServiceInterface
{
    /**
     * @var \Netgen\TagsBundle\API\Repository\TagsService
     */
    private $service;

    /**
     * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(TagsServiceInterface $service, EventDispatcherInterface $eventDispatcher)
    {
        $this->service = $service;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function loadTag(int $tagId, ?array $languages = null, bool $useAlwaysAvailable = true): Tag
    {
        return $this->service->loadTag($tagId, $languages, $useAlwaysAvailable);
    }

    public function loadTagList(array $tagIds, ?array $languages = null, bool $useAlwaysAvailable = true): TagList
    {
        return $this->service->loadTagList($tagIds, $languages, $useAlwaysAvailable);
    }

    public function loadTagByRemoteId(string $remoteId, ?array $languages = null, bool $useAlwaysAvailable = true): Tag
    {
        return $this->service->loadTagByRemoteId($remoteId, $languages, $useAlwaysAvailable);
    }

    public function loadTagByUrl(string $url, array $languages): Tag
    {
        return $this->service->loadTagByUrl($url, $languages);
    }

    public function loadTagChildren(?Tag $tag = null, int $offset = 0, int $limit = -1, ?array $languages = null, bool $useAlwaysAvailable = true): TagList
    {
        return $this->service->loadTagChildren($tag, $offset, $limit, $languages, $useAlwaysAvailable);
    }

    public function getTagChildrenCount(?Tag $tag = null, ?array $languages = null, bool $useAlwaysAvailable = true): int
    {
        return $this->service->getTagChildrenCount($tag, $languages, $useAlwaysAvailable);
    }

    public function loadTagsByKeyword(string $keyword, string $language, bool $useAlwaysAvailable = true, int $offset = 0, int $limit = -1): TagList
    {
        return $this->service->loadTagsByKeyword($keyword, $language, $useAlwaysAvailable, $offset, $limit);
    }

    public function getTagsByKeywordCount(string $keyword, string $language, bool $useAlwaysAvailable = true): int
    {
        return $this->service->getTagsByKeywordCount($keyword, $language, $useAlwaysAvailable);
    }

    public function searchTags(string $searchString, string $language, bool $useAlwaysAvailable = true, int $offset = 0, int $limit = -1): SearchResult
    {
        return $this->service->searchTags($searchString, $language, $useAlwaysAvailable, $offset, $limit);
    }

    public function loadTagSynonyms(Tag $tag, int $offset = 0, int $limit = -1, ?array $languages = null, bool $useAlwaysAvailable = true): TagList
    {
        return $this->service->loadTagSynonyms($tag, $offset, $limit, $languages, $useAlwaysAvailable);
    }

    public function getTagSynonymCount(Tag $tag, ?array $languages = null, bool $useAlwaysAvailable = true): int
    {
        return $this->service->getTagSynonymCount($tag, $languages, $useAlwaysAvailable);
    }

    public function getRelatedContent(Tag $tag, int $offset = 0, int $limit = -1, bool $returnContentInfo = true, array $additionalCriteria = [], array $sortClauses = []): array
    {
        return $this->service->getRelatedContent($tag, $offset, $limit, $returnContentInfo, $additionalCriteria, $sortClauses);
    }

    public function getRelatedContentCount(Tag $tag, array $additionalCriteria = []): int
    {
        return $this->service->getRelatedContentCount($tag, $additionalCriteria);
    }

    public function createTag(TagCreateStruct $tagCreateStruct): Tag
    {
        $beforeEvent = new Tags\BeforeCreateTagEvent($tagCreateStruct);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeCreateTagEvent::class)->isPropagationStopped()) {
            return $beforeEvent->getTag();
        }

        $tag = $beforeEvent->hasTag() ?
            $beforeEvent->getTag() :
            $this->service->createTag($tagCreateStruct);

        $this->eventDispatcher->dispatch(new Tags\CreateTagEvent($tagCreateStruct, $tag), APIEvents\CreateTagEvent::class);

        return $tag;
    }

    public function updateTag(Tag $tag, TagUpdateStruct $tagUpdateStruct): Tag
    {
        $beforeEvent = new Tags\BeforeUpdateTagEvent($tagUpdateStruct, $tag);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeUpdateTagEvent::class)->isPropagationStopped()) {
            return $beforeEvent->getUpdatedTag();
        }

        $updatedTag = $beforeEvent->hasUpdatedTag() ?
            $beforeEvent->getUpdatedTag() :
            $this->service->updateTag($tag, $tagUpdateStruct);

        $this->eventDispatcher->dispatch(new Tags\UpdateTagEvent($tagUpdateStruct, $updatedTag), APIEvents\UpdateTagEvent::class);

        return $updatedTag;
    }

    public function addSynonym(SynonymCreateStruct $synonymCreateStruct): Tag
    {
        $beforeEvent = new Tags\BeforeAddSynonymEvent($synonymCreateStruct);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeAddSynonymEvent::class)->isPropagationStopped()) {
            return $beforeEvent->getSynonym();
        }

        $synonym = $beforeEvent->hasSynonym() ?
            $beforeEvent->getSynonym() :
            $this->service->addSynonym($synonymCreateStruct);

        $this->eventDispatcher->dispatch(new Tags\AddSynonymEvent($synonymCreateStruct, $synonym), APIEvents\AddSynonymEvent::class);

        return $synonym;
    }

    public function convertToSynonym(Tag $tag, Tag $mainTag): Tag
    {
        $beforeEvent = new Tags\BeforeConvertToSynonymEvent($tag, $mainTag);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeConvertToSynonymEvent::class)->isPropagationStopped()) {
            return $beforeEvent->getSynonym();
        }

        $synonym = $beforeEvent->hasSynonym() ?
            $beforeEvent->getSynonym() :
            $this->service->convertToSynonym($tag, $mainTag);

        $this->eventDispatcher->dispatch(new Tags\ConvertToSynonymEvent($synonym, $mainTag), APIEvents\ConvertToSynonymEvent::class);

        return $synonym;
    }

    public function mergeTags(Tag $tag, Tag $targetTag): void
    {
        $beforeEvent = new Tags\BeforeMergeTagsEvent($tag, $targetTag);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeMergeTagsEvent::class)->isPropagationStopped()) {
            return;
        }

        $this->service->mergeTags($tag, $targetTag);

        $this->eventDispatcher->dispatch(new Tags\MergeTagsEvent($targetTag), APIEvents\MergeTagsEvent::class);
    }

    public function copySubtree(Tag $tag, ?Tag $targetParentTag = null): Tag
    {
        $beforeEvent = new Tags\BeforeCopySubtreeEvent($tag, $targetParentTag);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeCopySubtreeEvent::class)->isPropagationStopped()) {
            return $beforeEvent->getCopiedTag();
        }

        $copiedTag = $beforeEvent->hasCopiedTag() ?
            $beforeEvent->getCopiedTag() :
            $this->service->copySubtree($tag, $targetParentTag);

        $this->eventDispatcher->dispatch(new Tags\CopySubtreeEvent($tag, $copiedTag, $targetParentTag), APIEvents\CopySubtreeEvent::class);

        return $copiedTag;
    }

    public function moveSubtree(Tag $tag, ?Tag $targetParentTag = null): Tag
    {
        $beforeEvent = new Tags\BeforeMoveSubtreeEvent($tag, $targetParentTag);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeMoveSubtreeEvent::class)->isPropagationStopped()) {
            return $beforeEvent->getMovedTag();
        }

        $movedTag = $beforeEvent->hasMovedTag() ?
            $beforeEvent->getMovedTag() :
            $this->service->moveSubtree($tag, $targetParentTag);

        $this->eventDispatcher->dispatch(new Tags\MoveSubtreeEvent($movedTag, $targetParentTag), APIEvents\MoveSubtreeEvent::class);

        return $movedTag;
    }

    public function deleteTag(Tag $tag): void
    {
        $beforeEvent = new Tags\BeforeDeleteTagEvent($tag);

        if ($this->eventDispatcher->dispatch($beforeEvent, APIEvents\BeforeDeleteTagEvent::class)->isPropagationStopped()) {
            return;
        }

        $this->service->deleteTag($tag);

        $this->eventDispatcher->dispatch(new Tags\DeleteTagEvent($tag), APIEvents\DeleteTagEvent::class);
    }

    public function newTagCreateStruct(int $parentTagId, string $mainLanguageCode): TagCreateStruct
    {
        return $this->service->newTagCreateStruct($parentTagId, $mainLanguageCode);
    }

    public function newSynonymCreateStruct(int $mainTagId, string $mainLanguageCode): SynonymCreateStruct
    {
        return $this->service->newSynonymCreateStruct($mainTagId, $mainLanguageCode);
    }

    public function newTagUpdateStruct(): TagUpdateStruct
    {
        return $this->service->newTagUpdateStruct();
    }

    public function sudo(callable $callback, ?TagsServiceInterface $outerTagsService = null)
    {
        return $this->service->sudo($callback, $outerTagsService ?? $this);
    }
}
