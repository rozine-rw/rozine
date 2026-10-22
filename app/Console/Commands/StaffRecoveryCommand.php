<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Identity\ApproveStaffRecovery;
use App\Application\Identity\RequestStaffRecovery;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class StaffRecoveryCommand extends Command
{
    protected $signature = 'identity:staff-recovery {operation : request or approve}
        {--operator= : Independently enrolled dedicated account ID}
        {--kind= : genesis or recovery}
        {--recipient=* : Exactly two recipient account IDs}
        {--proposal= : Immutable proposal ID to approve}
        {--payload-sha256= : Independently reviewed proposal payload digest}
        {--reason= : Required audit reason}
        {--request-id= : Idempotency UUID, generated when omitted}';

    protected $description = 'Request or independently approve audited initial staff setup or recovery; no unilateral grant';

    public function handle(RequestStaffRecovery $request, ApproveStaffRecovery $approve): int
    {
        try {
            if (! $this->input->isInteractive()) {
                throw new IdentityViolation('STAFF_RECOVERY_INTERACTIVE_AUTHENTICATION_REQUIRED', 403);
            }
            $id = (string) $this->option('operator');
            $operation = (string) $this->argument('operation');
            if (! ctype_digit($id) || (int) $id < 1 || ! in_array($operation, ['request', 'approve'], true)) {
                throw new IdentityViolation('STAFF_RECOVERY_INPUT_INVALID', 422);
            }
            $password = (string) $this->secret('Current account password');
            $code = (string) $this->secret('Current authenticator code');
            $requestId = (string) ($this->option('request-id') ?: Str::uuid());
            $reason = (string) $this->option('reason');
            /** @var list<string> $ids */
            $ids = $this->option('recipient');
            foreach ($ids as $recipient) {
                if (! ctype_digit($recipient) || (int) $recipient < 1) {
                    throw new IdentityViolation('STAFF_RECOVERY_INPUT_INVALID', 422);
                }
            }
            $recipients = array_map(fn (string $value): int => (int) $value, $ids);
            $kind = (string) $this->option('kind');
            if (! in_array($kind, ['genesis', 'recovery'], true) || count($recipients) !== 2 || count(array_unique($recipients)) !== 2) {
                throw new IdentityViolation('STAFF_RECOVERY_INPUT_INVALID', 422);
            }
            if ($operation === 'request') {
                $result = $request->handle((int) $id, $password, $code, (string) $this->option('kind'),
                    $recipients, $reason, $requestId);
            } else {
                $this->line('Expected operation: '.$kind.'; recipient account IDs: '.implode(', ', $recipients));
                if (! $this->confirm('Approve the exact recipients and payload digest you independently reviewed?', false)) {
                    throw new IdentityViolation('STAFF_RECOVERY_APPROVAL_REQUIRED', 409);
                }
                $result = $approve->handle((int) $id, $password, $code, (string) $this->option('proposal'),
                    (string) $this->option('payload-sha256'), $reason, $requestId, $kind, $recipients);
            }
        } catch (IdentityViolation|CommandRejection $exception) {
            $this->components->error($exception->reason);

            return self::FAILURE;
        }
        $this->line(json_encode($result, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
