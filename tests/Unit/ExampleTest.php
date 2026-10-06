<?php

declare(strict_types=1);

test('runtime satisfies the minimum PHP version', function () {
    expect(PHP_VERSION_ID)->toBeGreaterThanOrEqual(80400);
});
