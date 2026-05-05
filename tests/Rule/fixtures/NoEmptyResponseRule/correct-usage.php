<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\NoEmptyResponseRule;

use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorrectUsage
{
    public function responseWithContent(): Response
    {
        return new Response('Hello World');
    }

    public function jsonResponseWithData(): JsonResponse
    {
        return new JsonResponse(['data' => 'value']);
    }

    public function jsonApiResponseWithData(): JsonApiResponse
    {
        return new JsonApiResponse(['data' => 'value']);
    }

    public function jsonResponseWithDefaultData(): JsonResponse
    {
        return new JsonResponse();
    }

    public function jsonApiResponseWithDefaultData(): JsonApiResponse
    {
        return new JsonApiResponse();
    }

    // Allowed empty status codes

    public function noContentResponse(): Response
    {
        return new Response('', 204);
    }

    public function noContentWithClassConstant(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function redirectResponse(): Response
    {
        return new Response('', 302);
    }

    public function redirectWithClassConstant(): Response
    {
        return new Response('', Response::HTTP_FOUND);
    }

    public function notModifiedResponse(): Response
    {
        return new Response('', 304);
    }

    public function noContentWithNamedStatus(): Response
    {
        return new Response(status: 204);
    }

    public function noContentWithNamedArguments(): Response
    {
        return new Response(status: Response::HTTP_NO_CONTENT, content: '');
    }

    // Response subclasses with non-body first parameter — should not be flagged

    public function emptyRedirectResponse(): RedirectResponse
    {
        return new RedirectResponse('/target');
    }

    public function emptyStreamedResponse(): StreamedResponse
    {
        return new StreamedResponse();
    }

    public function emptyBinaryFileResponse(): BinaryFileResponse
    {
        return new BinaryFileResponse('/path/to/file');
    }
}
