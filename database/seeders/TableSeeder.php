<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Table;
use Illuminate\Database\Seeder;
use RuntimeException;

class TableSeeder extends Seeder
{
    private const TOTAL_TABLES = 100;

    public function run(): void
    {
        $branches = Branch::query()->orderBy('id')->get();

        if ($branches->isEmpty()) {
            throw new RuntimeException('Không thể tạo bàn khi chưa có cơ sở.');
        }

        $tablesPerBranch = intdiv(self::TOTAL_TABLES, $branches->count());
        $remainingTables = self::TOTAL_TABLES % $branches->count();

        foreach ($branches as $branchIndex => $branch) {
            $branchTableCount = $tablesPerBranch + ($branchIndex < $remainingTables ? 1 : 0);

            for ($tableIndex = 1; $tableIndex <= $branchTableCount; $tableIndex++) {
                Table::create([
                    'branch_id' => $branch->id,
                    'name' => 'Bàn ' . str_pad($tableIndex, 2, '0', STR_PAD_LEFT),
                    'capacity' => 4,
                    'status' => 'empty',
                ]);
            }
        }
    }
}
