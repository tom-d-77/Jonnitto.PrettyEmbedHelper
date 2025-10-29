<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Jonnitto\PrettyEmbedPresentation\Service\ParseIDService;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\ResourceManagement\Exception;
use JsonException;

#[Flow\Scope('singleton')]
class MetadataService
{
    #[Flow\Inject]
    protected AssetService $assetService;

    #[Flow\Inject]
    protected YoutubeService $youtubeService;

    #[Flow\Inject]
    protected VimeoService $vimeoService;

    #[Flow\Inject]
    protected ParseIDService $parseID;

    #[Flow\Inject]
    protected ImageService $imageService;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @var array
     */
    protected $defaultReturn = ['node' => null];

    /**
     * Wrapper method to handle signals from Node::nodeAdded
     *
     * @param Node $node
     * @return array|null[]
     * @throws IllegalObjectTypeException
     */
    public function onNodeAdded(Node $node)
    {
        return $this->createDataFromService($node);
    }

    /**
     * Create data
     *
     * @param Node $node
     * @param bool $remove
     * @return array Information about the node
     * @throws IllegalObjectTypeException
     */
    public function createDataFromService(Node $node, bool $remove = false): array
    {
        $superTypes = $this->getSuperTypes($node);

        foreach ($superTypes as $superType) {
            if (
                $node->hasProperty('videoID') ||
                $superType->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata')
            ) {
                return $this->dataFromService($node, $remove);
            }
        }

        return $this->defaultReturn;
    }

    /**
     * Removes the metadata
     * @throws IllegalObjectTypeException
     */
    public function removeMetaData(Node $node): void
    {
        Utility::removeMetadata($this->contentRepositoryRegistry, $node);
        $this->imageService->removeTagIfEmpty();
    }

    /**
     * Update data
     *
     * @param Node $node
     * @param string $propertyName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return array Information about the node
     * @throws IllegalObjectTypeException
     */
    public function updateDataFromService(Node $node, string $propertyName, $oldValue, $newValue): array
    {
        $superTypeNames = array_keys($this->getSuperTypes($node));

        $platform = $this->checkNodeAndSetPlatform($node);

        if (
            ($propertyName === 'videoID' && $oldValue !== $newValue) ||
            ($propertyName === 'type' && $node->hasProperty('videoID')) ||
            ($propertyName === 'assets' &&
                in_array('Jonnitto.PrettyEmbedHelper:Mixin.Metadata', $superTypeNames))
        ) {
            return $this->dataFromService($node);
        }
        return $this->defaultReturn;
    }

    /**
     * Saves and returns the metadata
     *
     * @param Node $node
     * @param boolean $remove
     * @return array Information about the node
     * @throws IllegalObjectTypeException
     */
    protected function dataFromService(Node $node, bool $remove = false): array
    {
        $platform = $this->checkNodeAndSetPlatform($node);

        if (!$platform) {
            return $this->defaultReturn;
        }

        if ($platform == 'audio') {
            return $this->assetService->getAndSaveDataId3($node, $remove, 'Audio');
        }

        if ($platform == 'video') {
            return $this->assetService->getAndSaveDataId3($node, $remove, 'Video');
        }

        if ($platform == 'youtube') {
            try {
                $data = $this->youtubeService->getAndSaveDataFromApi($node, $remove);
            } catch (JsonException | InfiniteRedirectionException | IllegalObjectTypeException | InvalidQueryException | Exception $e) {
            }
            return $data ?? $this->defaultReturn;
        }

        if ($platform == 'vimeo') {
            return $this->vimeoService->getAndSaveDataFromApi($node, $remove);
        }

        return $this->defaultReturn;
    }

    /**
     * Check the node and return the platform/type
     *
     * @param Node $node
     * @return string|null
     */
    protected function checkNodeAndSetPlatform(Node $node): ?string
    {
        $superTypeStrings = array_keys($this->getSuperTypes($node));

        if (in_array('Jonnitto.PrettyEmbedAudio:Mixin.Assets', $superTypeStrings)) {
            return 'audio';
        }

        if (in_array('Jonnitto.PrettyEmbedVideo:Mixin.Assets', $superTypeStrings)) {
            return 'video';
        }

        if (
            !in_array('Jonnitto.PrettyEmbedHelper:Mixin.Metadata', $superTypeStrings)
        ) {
            return null;
        }

        $platform = $this->parseID->platform($node->getProperty('videoID'));

        if (!$platform) {
            Utility::removeMetadata($this->contentRepositoryRegistry, $node);
        }

        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        $contentRepository->handle(
            SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    'platform' => $platform,
                ]),
            ),
        );

        return $platform;
    }

    /**
     * @param Node $node
     * @return \Neos\ContentRepository\Core\NodeType\NodeType[]
     */
    public function getSuperTypes(Node $node): array
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        $nodeTypeManager = $contentRepository->getNodeTypeManager();

        $nodeType = $nodeTypeManager->getNodeType($node->nodeTypeName->value);
        return $nodeType->getDeclaredSuperTypes();
    }

    public function isNewVideoID(Node $node, string $videoID): bool
    {
        if (isset($node->getProperty('prettyembedMetadata')['href'])) {
            return (!str_contains($node->getProperty('prettyembedMetadata')['href'], $videoID));
        }
        return true;
    }
}
