<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidDisabledSslVerificationRule;

class CorrectUsage
{
    public function secureCurl(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    public function secureStreamContext(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);
    }
}
