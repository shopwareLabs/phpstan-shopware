<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidDisabledSslVerificationRule;

class WrongUsage
{
    public function curlVerifyPeerDisabled(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    public function curlVerifyHostDisabled(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    public function streamContextVerifyPeerDisabled(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
            ],
        ]);
    }
}
