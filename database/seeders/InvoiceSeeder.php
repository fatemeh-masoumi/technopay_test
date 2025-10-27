<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Database\Seeder;


class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn("No user found. Run UserSeeder first.");
            return;
        }

        Invoice::create([
            'user_id' => $user->id,
            'amount' => 150000,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addMinutes(30),
        ]);
    }
}
