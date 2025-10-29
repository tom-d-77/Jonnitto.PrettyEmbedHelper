<?php

namespace Jonnitto\PrettyEmbedHelper\CommandHook;

use Jonnitto\PrettyEmbedHelper\Service\MetadataService;
use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\EventStore\PublishedEvents;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

class AssetDownloadCommandHook implements CommandHookInterface
{

    #[Flow\Inject]
    protected LoggerInterface $logger;

    #[Flow\Inject]
    protected MetadataService $metadataService;

    public function __construct(
        private ContentGraphReadModelInterface $contentGraphReadModel
    ) {
    }

    public function onBeforeHandle(CommandInterface $command): CommandInterface
    {
        return $command;
    }

    public function onAfterHandle(CommandInterface $command, PublishedEvents $events): Commands
    {
        $this->logger->info(
            'Called onAfterHandle for command: ' . get_class($command),
            LogEnvironment::fromMethodName(__METHOD__)
        );

        if (!($command instanceof CreateNodeAggregateWithNode || $command instanceof SetNodeProperties) )
        {
            return Commands::createEmpty();
        }

        $contentGraph = $this->contentGraphReadModel->getContentGraph($command->workspaceName);

        $node = $contentGraph->getSubgraph(
            $command->originDimensionSpacePoint->toDimensionSpacePoint(),
            VisibilityConstraints::createEmpty()
        )->findNodeById($command->nodeAggregateId);

        $superTypeStrings = array_keys($this->metadataService->getSuperTypes($node));

        if (
            $command instanceOf CreateNodeAggregateWithNode &&
            in_array('Jonnitto.PrettyEmbedHelper:Mixin.Metadata', $superTypeStrings)
        )
        {
            $this->metadataService->onNodeAdded($node);
        }

        if (
            $command instanceOf SetNodeProperties &&
            in_array('Jonnitto.PrettyEmbedHelper:Mixin.Metadata', $superTypeStrings)
        )
        {
            // $this->metadataService->updateDataFromService($node);
        }

        return Commands::createEmpty();
    }
}
