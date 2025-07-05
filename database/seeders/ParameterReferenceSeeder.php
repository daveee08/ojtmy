<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParameterReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Get all unique agent_ids from agent_parameters
        $agents = DB::table('agent_parameters')->select('agent_id')->distinct()->get();

        foreach ($agents as $agent) {
            // Get all parameter IDs for this agent
            $parameterIds = DB::table('agent_parameters')
                ->where('agent_id', $agent->agent_id)
                ->pluck('id')
                ->toArray();

            // Convert to comma-separated string
            $parameterInputIds = implode(',', $parameterIds);

            // Insert into parameter_reference
            DB::table('parameter_reference')->insert([
                'agent_id' => $agent->agent_id,
                'parameter_input_ids' => $parameterInputIds,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
