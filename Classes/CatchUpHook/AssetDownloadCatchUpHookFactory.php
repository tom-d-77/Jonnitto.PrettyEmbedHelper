<?php

namespace Jonnitto\PrettyEmbedHelper\CatchUpHook;

use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;

class AssetDownloadCatchUpHookFactory implements CatchUpHookFactoryInterface
{

    public function build(CatchUpHookFactoryDependencies $dependencies): CatchUpHookInterface
    {
        return new AssetDownloadCatchUpHook();
    }
}
