<?php

namespace Jonnitto\PrettyEmbedHelper\CommandHook;

use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\Factory\CommandHookFactoryInterface;
use Neos\ContentRepository\Core\Factory\CommandHooksFactoryDependencies;
use Jonnitto\PrettyEmbedHelper\Service\MetadataService;
use Neos\Flow\Annotations as Flow;
#[Flow\Proxy(false)]
class AssetDownloadCommandHookFactory implements CommandHookFactoryInterface
{
    public function build(CommandHooksFactoryDependencies $commandHooksFactoryDependencies): CommandHookInterface
    {
        return new AssetDownloadCommandHook(
            $commandHooksFactoryDependencies->contentGraphReadModel
        );
    }
}
