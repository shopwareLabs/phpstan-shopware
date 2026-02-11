<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\NoEmptyResponseRule;

use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WrongUsage
{
    public function emptyResponse(): Response
    {
        return new Response();
    }

    public function emptyJsonResponse(): JsonResponse
    {
        return new JsonResponse();
    }

    public function emptyJsonApiResponse(): JsonApiResponse
    {
        return new JsonApiResponse();
    }

    public function blankStringResponse(): Response
    {
        return new Response('');
    }

    public function blankStringWithDefault200(): Response
    {
        return new Response('', 200);
    }

    public function blankStringWithClassConstant200(): Response
    {
        return new Response('', Response::HTTP_OK);
    }
}
