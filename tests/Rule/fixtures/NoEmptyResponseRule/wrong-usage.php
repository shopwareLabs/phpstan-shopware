<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\NoEmptyResponseRule;

use Symfony\Component\HttpFoundation\Response;

class WrongUsage
{
    public function emptyResponse(): Response
    {
        return new Response();
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

    public function nullBodyResponse(): Response
    {
        return new Response(null);
    }

    public function namedStatusWithDefaultEmptyBody(): Response
    {
        return new Response(status: 200);
    }

    public function namedStatusWithDefaultEmptyBodyConstant(): Response
    {
        return new Response(status: Response::HTTP_OK);
    }

    public function namedContentAndStatus(): Response
    {
        return new Response(status: 200, content: '');
    }

    public function reorderedNamedContentAndStatus(): Response
    {
        return new Response(content: '', status: 200);
    }
}
