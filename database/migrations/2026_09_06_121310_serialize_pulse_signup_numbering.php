<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Queue and loan numbers were allocated as `count() + 1` read outside any
     * lock, so two signups arriving together read the same count and were
     * issued the same number. This gives every series its own counter row that
     * is claimed under a row lock, and adds unique indexes so a bad allocation
     * can never persist even if a future caller bypasses the counter.
     */
    public function up(): void
    {
        Schema::create('signup_counters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        $this->resolveExistingCollisions('queue_number', ['type']);
        $this->resolveExistingCollisions('loan_number', []);

        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->unique(['type', 'queue_number']);
            $table->unique('loan_number');
        });

        $this->seedCounter('queue_number:investor', $this->highestIssued('queue_number', 'investor'));
        $this->seedCounter('queue_number:business', $this->highestIssued('queue_number', 'business'));
        $this->seedCounter('loan_number', $this->highestIssued('loan_number', null));
    }

    public function down(): void
    {
        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->dropUnique(['type', 'queue_number']);
            $table->dropUnique(['loan_number']);
        });

        Schema::dropIfExists('signup_counters');
    }

    /**
     * Reissue only the rows that actually collide, keeping the oldest holder of
     * each number. Renumbering everyone would be tidier and would also change
     * numbers that were already correct and already shown to the people holding
     * them, so the duplicates alone move.
     *
     * @param  list<string>  $scope
     */
    private function resolveExistingCollisions(string $column, array $scope): void
    {
        $groups = DB::table('pulse_signups')
            ->select([...$scope, $column, DB::raw('MIN(id) as keeper')])
            ->whereNotNull($column)
            ->groupBy([...$scope, $column])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $duplicates = DB::table('pulse_signups')
                ->where($column, $group->{$column})
                ->where('id', '!=', $group->keeper)
                ->when($scope !== [], fn (Builder $rows) => $rows->where('type', $group->type))
                ->orderBy('id')
                ->pluck('id');

            foreach ($duplicates as $id) {
                $type = $scope === [] ? null : $group->type;

                DB::table('pulse_signups')
                    ->where('id', $id)
                    ->update([$column => $this->format($column, $this->highestIssued($column, $type) + 1)]);
            }
        }
    }

    /**
     * The stored numbers are display strings such as `#0007` and `#1,204`, so
     * the highest issued value is read back through the digits rather than by
     * counting rows. Counting is what produced the collisions in the first
     * place, and a deleted row would make it hand out a number twice.
     */
    private function highestIssued(string $column, ?string $type): int
    {
        $issued = DB::table('pulse_signups')
            ->whereNotNull($column)
            ->when($type !== null, fn (Builder $rows) => $rows->where('type', $type))
            ->pluck($column);

        $highest = 0;

        foreach ($issued as $value) {
            $highest = max($highest, (int) preg_replace('/\D/', '', (string) $value));
        }

        return $highest;
    }

    private function format(string $column, int $value): string
    {
        return $column === 'loan_number'
            ? '#'.number_format($value)
            : '#'.str_pad((string) $value, 4, '0', STR_PAD_LEFT);
    }

    private function seedCounter(string $name, int $value): void
    {
        DB::table('signup_counters')->insert(['name' => $name, 'value' => $value]);
    }
};
