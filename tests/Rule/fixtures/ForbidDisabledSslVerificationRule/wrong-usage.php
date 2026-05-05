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

    public function curlVerifyPeerDisabledWithZero(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function curlVerifyHostDisabled(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    public function curlVerifyHostDisabledWithFalse(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    public function streamContextVerifyPeerDisabled(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
            ],
        ]);
    }

    public function streamContextVerifyPeerNameDisabled(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer_name' => false,
            ],
        ]);
    }

    public function streamContextVerifyPeerNameDisabledWithZero(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer_name' => 0,
            ],
        ]);
    }
}
