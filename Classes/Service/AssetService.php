<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Flow\Utility\Exception;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use function file_exists;
use function round;

#[Flow\Scope('singleton')]
class AssetService
{
    #[Flow\Inject]
    protected Environment $environment;

    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Set cache directory
     *
     * @return void
     */
    protected function setCacheDirectory(): void
    {
        if (class_exists('JamesHeinrich\GetID3\Utils')) {
            try {
                $cacheDirectory = Files::concatenatePaths([
                    $this->environment->getPathToTemporaryDirectory(),
                    (string) $this->environment->getContext(),
                    'Jonnitto_PrettyEmbedHelper_GetID3_Cache',
                ]);
                Files::createDirectoryRecursively($cacheDirectory);
                \JamesHeinrich\GetID3\Utils::setTempDirectory($cacheDirectory);
            } catch (Exception | FilesException $e) {
            }
        }
    }

    /**
     * Save the duration in seconds from audio or video files
     *
     * @param Node $node
     * @param boolean $remove
     * @param string $type
     * @return array
     */
    public function getAndSaveDataId3(Node $node, bool $remove, string $type): array
    {
        $duration = null;

        if ($remove === true || !class_exists('JamesHeinrich\GetID3\GetID3')) {
            Utility::removeMetadata($this->contentRepositoryRegistry, $node, 'duration');
            return [];
        }

        $this->setCacheDirectory();
        $assets = $node->getProperty('assets');
        $duration = null;

        if (is_iterable($assets)) {
            foreach ($assets as $asset) {
                try {
                    if (!method_exists($asset, 'getResource')) {
                        continue;
                    }

                    $resource = $asset->getResource();
                    if ($resource === null) {
                        continue;
                    }

                    $file = $resource->createTemporaryLocalCopy();
                    if ($file === false || !file_exists($file)) {
                        continue;
                    }

                    $getID3 = new \JamesHeinrich\GetID3\GetID3();
                    $fileInfo = $getID3->analyze($file);

                    if (isset($fileInfo['playtime_seconds'])) {
                        $duration = (int) round($fileInfo['playtime_seconds']);
                        break; // Use the first valid asset with duration
                    }
                } catch (\Exception $e) {
                    // Log error or handle it as needed
                    continue;
                }
            }
        }

        if ($duration !== null) {
            Utility::setMetadata($this->contentRepositoryRegistry, $node, 'duration', $duration);
        }

        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        $subGraph = $contentRepository->getContentSubgraph($node->workspaceName, $node->dimensionSpacePoint);
        $absoluteNodePath = $subGraph->retrieveNodePath($node->aggregateId);

        return [
            'nodeTypeName' => $node->nodeTypeName->value,
            'node' => $type,
            'type' => '',
            'id' => '',
            'path' => $absoluteNodePath->path->serializeToString(),
            'data' => isset($duration),
        ];
    }
}
