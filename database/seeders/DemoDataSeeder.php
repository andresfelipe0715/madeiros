<?php

namespace Database\Seeders;

use App\Models\Stage;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                'phone' => '3'.$faker->numerify('#########'),
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

        $orderCount = 1000;

        $this->command->info("Creating $orderCount orders...");

        for ($i = 0; $i < $orderCount; $i++) {

            $createdDate = Carbon::now()->subDays(rand(0, 90));
            $creator = $users->random()->id;

            $orderId = DB::table('orders')->insertGetId([
                'client_id' => $faker->randomElement($clientIds),
                'invoice_number' => 'INV-'.str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'created_by' => $creator,
                'created_at' => $createdDate,
                'updated_at' => $createdDate,
            ]);

            DB::table('order_logs')->insert([
                'order_id' => $orderId,
                'user_id' => $creator,
                'action' => 'Orden creada',
                'created_at' => $createdDate,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create all stages as unstarted
            |--------------------------------------------------------------------------
            */

            foreach ($stages as $stage) {
                DB::table('order_stages')->insert([
                    'order_id' => $orderId,
                    'stage_id' => $stage->id,
                    'sequence' => $stage->default_sequence,
                    'started_at' => null,
                    'completed_at' => null,
                    'started_by' => null,
                    'completed_by' => null,
                    'created_at' => $createdDate,
                    'updated_at' => $createdDate,
                ]);
            }
        }

        $this->command->info('Demo dataset ready.');
    }
}
