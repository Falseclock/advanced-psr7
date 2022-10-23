<?php
/**
 * Special index file to test requests via HTTP
 */

declare(strict_types=1);

namespace Falseclock\AdvancedPSR7\Tests;

use Falseclock\AdvancedPSR7\HttpRequest;

require __DIR__ . '/../vendor/autoload.php';

$request = HttpRequest::fromGlobals();

$body = $request->getParsedBody();
phpinfo();
