<?php

declare(strict_types=1);

return [
    // Inactive until the owner independently approves enrollment. Map dedicated user IDs to
    // their existing, verified staff-person identity digests; never to self-declared labels.
    // This authority is separate from identity operators and every ordinary staff role.
    'operators' => [],
];
