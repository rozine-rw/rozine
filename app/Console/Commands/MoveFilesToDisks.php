<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Business\Contracts\BusinessPhotos;
use App\Application\Environment\EnvironmentIsolation;
use App\Casts\StoredFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Moves the files already kept in database columns onto the configured disks: private documents to
 * the files disk, still encrypted, and business photos to the public media disk. Each private
 * table is rewritten in one transaction with its immutability trigger disabled and restored, as
 * the statement filename migration does; only the column's storage changes, never the bytes. It
 * refuses production, where files stay in the database until the privacy pledge work.
 */
class MoveFilesToDisks extends Command
{
    /** The private file tables and the trigger that keeps each one immutable. */
    public const array PRIVATE_TABLES = [
        'statement_originals' => 'statement_originals_immutable',
        'auditor_certificates' => 'auditor_certificates_immutable',
        'audit_ledger_originals' => 'audit_ledger_originals_immutable',
        'audit_dispute_proofs' => 'audit_dispute_proofs_immutable',
        'investor_verification_documents' => 'investor_verification_documents_immutable',
    ];

    protected $signature = 'files:move {--dry-run : Count the files that would move without moving them}';

    protected $description = 'Move uploaded files from the database onto the files and media disks';

    public function handle(EnvironmentIsolation $environment, BusinessPhotos $photos): int
    {
        if ($environment->profile() === 'production') {
            $this->error('Files stay in the database in production.');

            return self::FAILURE;
        }
        $dryRun = (bool) $this->option('dry-run');
        if (! is_string(config('filesystems.files_disk'))) {
            $this->warn('No files disk is configured; private files stay in the database.');
        } else {
            foreach (self::PRIVATE_TABLES as $table => $trigger) {
                $this->line("{$table}: ".$this->movePrivate($table, $trigger, $dryRun));
            }
        }
        if (! is_string(config('filesystems.media_disk'))) {
            $this->warn('No media disk is configured; business photos stay in the database.');
        } else {
            $this->line('business_photos: '.$photos->relocate($dryRun));
        }

        return self::SUCCESS;
    }

    private function movePrivate(string $table, string $trigger, bool $dryRun): int
    {
        $pending = DB::table($table)->where('content', 'not like', StoredFile::PREFIX.'%');
        if ($dryRun) {
            return $pending->count();
        }

        return DB::transaction(function () use ($table, $trigger, $pending): int {
            DB::statement("ALTER TABLE {$table} DISABLE TRIGGER {$trigger}");
            $moved = 0;
            $pending->orderBy('id')->eachById(function (object $row) use ($table, &$moved): void {
                DB::table($table)->where('id', $row->id)->update(['content' => StoredFile::write($table, $row->content)]);
                $moved++;
            });
            DB::statement("ALTER TABLE {$table} ENABLE TRIGGER {$trigger}");

            return $moved;
        });
    }
}
