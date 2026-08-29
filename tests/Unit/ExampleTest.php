<?php

test('runtime satisfies the minimum PHP version', function () {
    expect(PHP_VERSION_ID)->toBeGreaterThanOrEqual(80400);
});
