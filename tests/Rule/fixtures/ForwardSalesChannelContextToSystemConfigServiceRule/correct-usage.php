<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForwardSalesChannelContextToSystemConfigServiceRule;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CorrectUsage
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function correct(SalesChannelContext $salesChannelContext): void
    {
        $this->systemConfigService->get('foo.bar', $salesChannelContext->getSalesChannelId());
        $this->systemConfigService->getString('foo.bar', $salesChannelContext->getSalesChannel()->getId());
        $this->systemConfigService->getInt('foo.bar', $salesChannelContext->getSalesChannelId());
        $this->systemConfigService->getFloat('foo.bar', $salesChannelContext->getSalesChannelId());
        $this->systemConfigService->getBool('foo.bar', $salesChannelContext->getSalesChannelId());
    }

    public function correctWithoutContext(): void
    {
        $this->systemConfigService->get('foo.bar');
    }

    public function correctWithStringVariable(SalesChannelContext $salesChannelContext): void
    {
        $salesChannelId = $salesChannelContext->getSalesChannelId();

        $this->systemConfigService->get('foo.bar', $salesChannelId);
    }
}
