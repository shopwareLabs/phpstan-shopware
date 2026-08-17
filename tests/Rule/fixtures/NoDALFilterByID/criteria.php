<?php

declare(strict_types=1);

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;

$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('id', '12345'));

// This should be allowed when wrapped in MultiFilter
$criteria->addFilter(new MultiFilter(
    MultiFilter::CONNECTION_OR,
    [
        new EqualsFilter('id', '12345'),
    ],
));

// This should be allowed when wrapped in NotFilter
$criteria->addFilter(new NotFilter(
    MultiFilter::CONNECTION_AND,
    [
        new EqualsAnyFilter('id', ['123', '456']),
    ],
));

// This should be allowed when wrapped in any nested filter.
$criteria->addFilter(new OrFilter([
    new EqualsAnyFilter('id', ['123', '456']),
]));
