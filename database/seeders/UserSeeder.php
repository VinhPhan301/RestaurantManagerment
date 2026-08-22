<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const STAFF_COUNT = 100;

    public function run(): void
    {
        $branches = Branch::query()->orderBy('id')->get();

        if ($branches->isEmpty()) {
            return;
        }

        $password = Hash::make('password');

        foreach ($branches as $index => $branch) {
            $managerNumber = $index + 1;

            User::updateOrCreate(
                ['email' => "manager{$managerNumber}@restaurant.com"],
                [
                    'name' => "Manager {$managerNumber}",
                    'password' => $password,
                    'role' => 'manager',
                    'branch_id' => $branch->id,
                ],
            );
        }

        $branchCount = $branches->count();
        $baseStaffPerBranch = intdiv(self::STAFF_COUNT, $branchCount);
        $remainingStaff = self::STAFF_COUNT % $branchCount;
        $staffNumber = 1;

        foreach ($branches as $index => $branch) {
            $staffForBranch = $baseStaffPerBranch + ($index < $remainingStaff ? 1 : 0);

            for ($i = 0; $i < $staffForBranch; $i++) {
                User::updateOrCreate(
                    ['email' => "staff{$staffNumber}@restaurant.com"],
                    [
                        'name' => "Staff {$staffNumber}",
                        'password' => $password,
                        'role' => 'staff',
                        'branch_id' => $branch->id,
                    ],
                );

                $staffNumber++;
            }
        }
    }
}
