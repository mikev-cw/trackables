<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateMysqlToPostgres extends Command
{
    protected $signature = 'trackables:migrate-mysql-to-pgsql
        {--from=mysql_legacy : Source database connection}
        {--to=pgsql : Target PostgreSQL connection}
        {--chunk=500 : Rows copied per batch}
        {--truncate : Truncate target tables before copying}
        {--dry-run : Show what would be copied without writing}
        {--force : Run without interactive confirmation}';

    protected $description = 'Copy Trackables data from a legacy MySQL database into a migrated PostgreSQL database.';

    /**
     * Keep this list in dependency order. Runtime tables such as sessions,
     * cache, and jobs are intentionally excluded.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'users',
        'password_reset_tokens',
        'trackable_groups',
        'trackables',
        'enums',
        'enum_values',
        'trackable_schemas',
        'trackable_records',
        'trackable_data',
        'trackable_graphs',
        'personal_access_tokens',
    ];

    public function handle(): int
    {
        $sourceName = (string) $this->option('from');
        $targetName = (string) $this->option('to');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if ($sourceName === $targetName) {
            $this->error('Source and target connections must be different.');

            return self::FAILURE;
        }

        $source = DB::connection($sourceName);
        $target = DB::connection($targetName);

        if ($target->getDriverName() !== 'pgsql') {
            $this->error("Target connection [{$targetName}] must use the pgsql driver.");

            return self::FAILURE;
        }

        $this->line("Source: {$sourceName} ({$source->getDriverName()})");
        $this->line("Target: {$targetName} ({$target->getDriverName()})");

        $this->assertTablesExist($source, $target);

        if ($dryRun) {
            $this->info('Dry run only. No target rows will be changed.');
            $this->reportSourceCounts($source);

            return self::SUCCESS;
        }

        if (! (bool) $this->option('force')) {
            $message = (bool) $this->option('truncate')
                ? 'This will truncate target data tables and copy legacy MySQL data. Continue?'
                : 'This will copy legacy MySQL data into the target PostgreSQL database. Continue?';

            if (! $this->confirm($message, false)) {
                $this->warn('Migration cancelled.');

                return self::FAILURE;
            }
        }

        DB::connection($sourceName)->disableQueryLog();
        DB::connection($targetName)->disableQueryLog();

        if ((bool) $this->option('truncate')) {
            $this->truncateTargetTables($target);
        } else {
            $this->assertTargetTablesAreEmpty($target);
        }

        foreach ($this->tables as $table) {
            $this->copyTable($source, $target, $table, $chunkSize);
        }

        $this->resetPostgresSequences($target);

        $this->info('MySQL to PostgreSQL data copy completed.');

        return self::SUCCESS;
    }

    private function assertTablesExist(Connection $source, Connection $target): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::connection($source->getName())->hasTable($table)) {
                $this->warn("Source table [{$table}] does not exist. It will be skipped.");
            }

            if (! Schema::connection($target->getName())->hasTable($table)) {
                throw new \RuntimeException("Target table [{$table}] does not exist. Run PostgreSQL migrations first.");
            }
        }
    }

    private function reportSourceCounts(Connection $source): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::connection($source->getName())->hasTable($table)) {
                continue;
            }

            $this->line(str_pad($table, 28).$source->table($table)->count());
        }
    }

    private function assertTargetTablesAreEmpty(Connection $target): void
    {
        foreach ($this->tables as $table) {
            $count = $target->table($table)->count();

            if ($count > 0) {
                throw new \RuntimeException(
                    "Target table [{$table}] already has {$count} rows. Use --truncate to replace target data."
                );
            }
        }
    }

    private function truncateTargetTables(Connection $target): void
    {
        $tables = collect($this->tables)
            ->map(fn (string $table) => $target->getQueryGrammar()->wrapTable($table))
            ->implode(', ');

        $this->warn('Truncating target data tables...');
        $target->statement("TRUNCATE TABLE {$tables} RESTART IDENTITY CASCADE");
    }

    private function copyTable(Connection $source, Connection $target, string $table, int $chunkSize): void
    {
        if (! Schema::connection($source->getName())->hasTable($table)) {
            return;
        }

        $total = $source->table($table)->count();
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat("Copying {$table}: %current%/%max% [%bar%] %percent:3s%%");
        $bar->start();

        $source->table($table)
            ->orderBy($this->orderColumn($source, $table))
            ->chunk($chunkSize, function ($rows) use ($target, $table, $bar) {
                $payload = $rows
                    ->map(fn ($row) => (array) $row)
                    ->values()
                    ->all();

                if ($payload !== []) {
                    $target->table($table)->insert($payload);
                }

                $bar->advance(count($payload));
            });

        $bar->finish();
        $this->newLine();
    }

    private function orderColumn(Connection $source, string $table): string
    {
        foreach (['id', 'uid', 'email'] as $column) {
            if (Schema::connection($source->getName())->hasColumn($table, $column)) {
                return $column;
            }
        }

        return Schema::connection($source->getName())->getColumnListing($table)[0];
    }

    private function resetPostgresSequences(Connection $target): void
    {
        foreach (['users' => 'id', 'personal_access_tokens' => 'id'] as $table => $column) {
            $sequence = $target->selectOne('SELECT pg_get_serial_sequence(?, ?) AS sequence', [$table, $column])?->sequence;

            if (! $sequence) {
                continue;
            }

            $target->statement(
                "SELECT setval(?, COALESCE((SELECT MAX({$column}) FROM {$table}), 1), (SELECT COUNT(*) > 0 FROM {$table}))",
                [$sequence]
            );
        }
    }
}
