<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Testing;

use MakinaCorpus\QueryBuilder\BridgeFactory;
use MakinaCorpus\QueryBuilder\Bridge\Bridge;

trait FunctionalDoctrineTestCaseTrait
{
    use FunctionalTestCaseTrait;

    #[\Override]
    protected function doCreateBridge(array $params): Bridge
    {
        return BridgeFactory::createDoctrine($params);
    }
}
