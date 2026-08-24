<?php

declare(strict_types=1);

namespace Test\App;

/** @var \Grpc\InterceptorChannel $channel */
$channel = new \stdClass();
$channel->getTarget();
