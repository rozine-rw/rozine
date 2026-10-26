<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class IngestStatementRequest extends BusinessCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['file' => ['required', 'file', 'max:10240']];
    }
}
