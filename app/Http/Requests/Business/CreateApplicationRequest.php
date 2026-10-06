<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class CreateApplicationRequest extends BusinessCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [];
    }
}
