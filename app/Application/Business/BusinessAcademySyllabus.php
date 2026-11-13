<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\AcademyAttemptStore;
use App\Application\Business\Contracts\AcademyContent;
use App\Domain\Business\ReadinessAcademy;
use App\Domain\Business\ReadinessStage;

/**
 * What a Business studies in the Academy (BRS v3.0 RDY-R07): the core modules of its readiness
 * stage, each with where it stands, and the next stage's core as a preview. Called only with a
 * Business id already read under the caller's own Business authority.
 *
 * @phpstan-import-type Module from ReadinessAcademy
 * @phpstan-import-type Attempt from ReadinessAcademy
 * @phpstan-import-type Status from ReadinessAcademy
 *
 * @phpstan-type Syllabus array{stage: int|null, level: int, core: list<array{key: string, module: Module, status: Status, attempts: list<Attempt>}>,
 *     preview: list<array{key: string, module: Module}>}
 */
final readonly class BusinessAcademySyllabus
{
    public function __construct(private AcademyContent $content, private AcademyAttemptStore $attempts, private PublishedMonthlyReports $reports,
        private ReadinessStage $stage) {}

    /** @return Syllabus */
    public function forBusiness(string $businessId): array
    {
        $stage = $this->stage->assess(ReadinessStage::months($this->reports->forBusiness($businessId)))['stage'];
        $level = ReadinessAcademy::level($stage);
        $attempts = $this->attempts->forBusiness($businessId);
        $now = now('UTC')->toDateTimeImmutable();
        $core = [];
        $preview = [];
        foreach ($this->content->modules() as $key => $module) {
            if ($module['stage'] === $level) {
                $own = array_values(array_filter($attempts, fn (array $attempt): bool => $attempt['module'] === $key));
                $core[] = ['key' => $key, 'module' => $module, 'status' => ReadinessAcademy::status($own, $now), 'attempts' => $own];
            } elseif ($module['stage'] === $level + 1) {
                $preview[] = ['key' => $key, 'module' => $module];
            }
        }

        return ['stage' => $stage, 'level' => $level, 'core' => $core, 'preview' => $preview];
    }
}
