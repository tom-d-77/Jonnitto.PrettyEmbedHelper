<?php

namespace Jonnitto\PrettyEmbedHelper\CatchUpHook;

use Jonnitto\PrettyEmbedHelper\Service\MetadataService;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;


class AssetDownloadCatchUpHookFactory implements CatchUpHookFactoryInterface
{
    public function __construct(
        private MetadataService $metadataService
    ) {

    }

    public function build(CatchUpHookFactoryDependencies $dependencies): CatchUpHookInterface
    {
        return new AssetDownloadCatchUpHook(
            $dependencies->contentRepositoryId,
            $dependencies->projectionState,
            $dependencies->nodeTypeManager,
            $this->metadataService
        );
    }
}
