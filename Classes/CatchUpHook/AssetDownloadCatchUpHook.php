<?php

namespace Jonnitto\PrettyEmbedHelper\CatchUpHook;

use Jonnitto\PrettyEmbedHelper\Service\MetadataService;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

class AssetDownloadCatchUpHook implements CatchUpHookInterface
{

    #[Flow\Inject]
    protected LoggerInterface $logger;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel,
        private readonly NodeTypeManager $nodeTypeManager,
        private MetadataService $metadataService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function onBeforeCatchUp(SubscriptionStatus $subscriptionStatus): void
    {
        // TODO: Implement onBeforeCatchUp() method.
    }

    /**
     * @inheritDoc
     */
    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        // TODO: Implement onBeforeEvent() method.
    }

    /**
     * @inheritDoc
     */
    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if ($eventInstance instanceof NodeAggregateWithNodeWasCreated) {
            $this->logger->info('NodeAggregateWithNodeWasCreated', ['event' => $eventInstance::class]);
            // $this->metadataService->onNodeAdded();
        }

        if ($eventInstance instanceof NodePropertiesWereSet) {
            $contentGraph = $this->contentGraphReadModel->getContentGraph($eventInstance->getWorkspaceName());
            $node = $contentGraph->getSubgraph(
                $eventInstance->originDimensionSpacePoint->toDimensionSpacePoint(),  // right dimension?
                VisibilityConstraints::createEmpty()
            )->findNodeById($eventInstance->nodeAggregateId);
            $this->logger->info(
                'NodePropertiesWereSet: ' . $node->nodeTypeName . ' ' . $node->aggregateId,
                LogEnvironment::fromMethodName(__METHOD__));
            // $this->metadataService->updateDataFromService($node);
        }
    }

    /**
     * @inheritDoc
     */
    public function onAfterBatchCompleted(): void
    {
        // TODO: Implement onAfterBatchCompleted() method.
    }

    /**
     * @inheritDoc
     */
    public function onAfterCatchUp(): void
    {
        // TODO: Implement onAfterCatchUp() method.
    }
}
