<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\RecordAuditEngagementTerms;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

class RecordAuditEngagementTermsCommand extends Command
{
    protected $signature = 'auditor:engagement-terms {actor : Current compliance staff account ID} {file : Local JSON containing status, version, documents, synthetic, approval_reference and reason} {--expected-revision= : Current catalog revision, or 0 for an empty catalog} {--request-id= : UUID retained for identical retries}';

    protected $description = 'Publish or withdraw retained Auditor engagement terms through current compliance authority';

    public function handle(RecordAuditEngagementTerms $action, Filesystem $files): int
    {
        $path = realpath((string) $this->argument('file'));
        if ($path === false || ! $files->isFile($path) || ! $files->isReadable($path) || $files->size($path) > 5242880) {
            $this->components->error('AUDIT_ENGAGEMENT_FILE_INVALID: supply a readable local JSON file of at most 5 MiB.');

            return self::FAILURE;
        }

        try {
            $payload = json_decode($files->get($path), true, 16, JSON_THROW_ON_ERROR);
            /** @var array{actor_id: int|string, expected_revision: int|string, request_id: string, payload: array{status: string, version: string|null, documents: array<string, mixed>, synthetic: bool, approval_reference: string, reason: string}} $input */
            $input = Validator::make(['actor_id' => $this->argument('actor'), 'expected_revision' => $this->option('expected-revision'),
                'request_id' => $this->option('request-id'), 'payload' => $payload], [
                    'actor_id' => ['required', 'integer', 'min:1'],
                    'expected_revision' => ['required', 'integer', 'min:0'],
                    'request_id' => ['required', 'uuid'],
                    'payload' => ['required', 'array:status,version,documents,synthetic,approval_reference,reason'],
                    'payload.status' => ['required', 'in:active,withdrawn'],
                    'payload.version' => ['present', 'nullable', 'string'],
                    'payload.documents' => ['present', 'array'],
                    'payload.synthetic' => ['required', 'boolean:strict'],
                    'payload.approval_reference' => ['required', 'string'],
                    'payload.reason' => ['required', 'string'],
                ])->validate();
            $document = $input['payload'];
            $result = $action->handle((int) $input['actor_id'], (int) $input['expected_revision'], $document['status'], $document['version'],
                $document['documents'], $document['synthetic'], $document['approval_reference'], $document['reason'], $input['request_id']);
        } catch (FileNotFoundException|JsonException) {
            $this->components->error('AUDIT_ENGAGEMENT_FILE_INVALID: supply a readable JSON release object.');

            return self::FAILURE;
        } catch (ValidationException $exception) {
            $this->components->error('AUDIT_ENGAGEMENT_INPUT_INVALID: '.implode(', ', array_keys($exception->errors())));

            return self::FAILURE;
        } catch (IdentityViolation|CommandRejection $exception) {
            $this->components->error($exception->reason);

            return self::FAILURE;
        }

        if ($result['status'] !== 'completed') {
            $this->components->error($result['code'].' (revision: '.($result['revision'] ?? 'unavailable').')');

            return self::FAILURE;
        }

        /** @var array{release_id: string, sha256: string} $data */
        $data = $result['data'];
        $this->components->info((string) $result['code']);
        $this->line('release='.$data['release_id'].' revision='.$result['revision'].' sha256='.$data['sha256']);

        return self::SUCCESS;
    }
}
