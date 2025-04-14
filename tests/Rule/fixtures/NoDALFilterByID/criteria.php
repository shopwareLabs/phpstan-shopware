<?php

declare(strict_types=1);

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

// This should trigger an error
$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('id', '12345'));

// This should be allowed (inside MultiFilter)
$ids = ['1', '2', '3'];
$criteria->addPostFilter(
    new MultiFilter(
        MultiFilter::CONNECTION_OR,
        [
            new EqualsAnyFilter('parentId', $ids),
            new EqualsAnyFilter('id', $ids),
        ]
    )
);
