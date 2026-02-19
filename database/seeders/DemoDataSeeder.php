<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Stage;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_CO');

        Schema::disableForeignKeyConstraints();
        DB::table('order_tracking_links')->truncate();
        DB::table('order_logs')->truncate();
        DB::table('order_stages')->truncate();
        DB::table('orders')->truncate();
        DB::table('clients')->truncate();
        Schema::enableForeignKeyConstraints();

        $users = User::all();
        $stages = Stage::orderBy('default_sequence')->get();

        if ($users->isEmpty() || $stages->isEmpty()) {
            $this->command->error('Users or stages missing.');
            return;
        }

        DB::table('file_types')->updateOrInsert(['name' => 'archivo_orden']);

        /*
        |--------------------------------------------------------------------------
        | CLIENTS
        |--------------------------------------------------------------------------
        */
        $clients = [];

        for ($i = 0; $i < 250; $i++) {
            $clients[] = [
                'name' => $faker->company,
                'document' => $faker->unique()->numerify('#########'),
                'phone' => '3' . $faker->numerify('#########'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('clients')->insert($clients);
        $clientIds = DB::table('clients')->pluck('id')->toArray();

        /*
        |--------------------------------------------------------------------------
        | ORDERS
        |--------------------------------------------------------------------------
        */

        $materials = ['Melamina Blanca', 'MDF RH', 'Triplex', 'Aglomerado', 'Pino'];
        $orderCount = 1000;

        $this->command->info("Creating $orderCount orders...");

        for ($i = 0; $i < $orderCount; $i++) {

            $createdDate = Carbon::now()->subDays(rand(0, 90));
            $creator = $users->random()->id;

            $orderId = DB::table('orders')->insertGetId([
                'client_id' => $faker->randomElement($clientIds),
                'material' => $faker->randomElement($materials),
                'invoice_number' => 'INV-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'created_by' => $creator,
                'created_at' => $createdDate,
                'updated_at' => $createdDate,
            ]);

            DB::table('order_logs')->insert([
                'order_id' => $orderId,
                'user_id' => $creator,
                'action' => 'Orden creada',
                'created_at' => $createdDate
            ]);

            /*
            |--------------------------------------------------------------------------
            | Determine progression depth
            |--------------------------------------------------------------------------
            */

            $r = rand(1, 100);

            if ($r <= 25) {
                // not started
                continue;
            }

            if ($r <= 40) {
                // waiting first stage
                continue;
            }

            // spread mid workflow across stages
            if ($r <= 80) {
                $progressionDepth = rand(1, $stages->count() - 1);
            } else {
                // fully completed
                $progressionDepth = $stages->count();
            }

            $currentTime = $createdDate->copy()->addMinutes(20);

            foreach ($stages as $index => $stage) {

                if ($index >= $progressionDepth) {
                    break;
                }

                $worker = $users->random()->id;

                $start = $currentTime->copy()->addMinutes(rand(5, 60));
                $duration = rand(10, 120);
                $finish = $start->copy()->addMinutes($duration);

                DB::table('order_stages')->insert([
                    'order_id' => $orderId,
                    'stage_id' => $stage->id,
                    'sequence' => $stage->default_sequence,
                    'started_at' => $start,
                    'completed_at' => $finish,
                    'started_by' => $worker,
                    'completed_by' => $worker,
                    'created_at' => $start,
                    'updated_at' => $finish,
                ]);

                DB::table('order_logs')->insert([
                    'order_id' => $orderId,
                    'user_id' => $worker,
                    'action' => "Etapa completada: {$stage->name}",
                    'created_at' => $finish
                ]);

                $currentTime = $finish->copy();

                if ($stage->is_delivery_stage) {

                    DB::table('orders')->where('id', $orderId)->update([
                        'delivered_at' => $finish,
                        'delivered_by' => $worker
                    ]);

                    DB::table('order_tracking_links')->insert([
                        'order_id' => $orderId,
                        'token' => Str::random(32),
                        'expires_at' => now()->addDays(30),
                        'created_at' => $finish
                    ]);
                }
            }
        }

        $this->command->info('Demo dataset ready.');
    }
}
