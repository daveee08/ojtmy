<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http, Log, Auth, DB};
use App\Models\{Message, ParameterInput};

class BackendServiceController extends Controller
{
    /**
     * Resolve agent and all its required parameters.
     */
    protected function resolveAgent(string $agentName)
    {
        $agent = DB::table('agents')->where('agent', $agentName)->first();
        if (!$agent) abort(404, 'Agent not found');

        return $agent;
    }

    /**
     * Resolve all parameter inputs for this agent.
     * 
     * @param object $agent
     * @param array $requestData (e.g., ['grade_level' => '8', 'writing_style' => 'formal'])
     * @return array ['param_key' => ParameterInput]
     */
    protected function resolveAllParameterInputs($agent, array $requestData)
    {
        $paramInputs = [];

        $parameters = DB::table('agent_parameters')
            ->where('agent_id', $agent->id)
            ->get();

        foreach ($parameters as $param) {
            $input = $requestData[$param->parameter] ?? ParameterInput::where([
                ['parameter_id', '=', $param->id],
                ['agent_id', '=', $agent->id],
            ])->whereNotNull('input')->latest()->value('input');

            // // Fallback to user attribute
            // if (!$input && $param->parameter === 'grade_level') {
            //     $input = Auth::user()->grade_level;
            // }

            if (!$input) {
                abort(400, "Missing input for required parameter: {$param->parameter}");
            }

            $paramInputs[$param->parameter] = ParameterInput::firstOrCreate([
                'input' => $input,
                'agent_id' => $agent->id,
                'parameter_id' => $param->id,
            ]);
        }

        return $paramInputs;
    }

    /**
     * Create and link a human message.
     */
    protected function createHumanMessage($agentId, $userId, $topic, $gradeLevel, $parameterInputIds, $messageId = null)
    {
        $human = Message::create([
            'agent_id' => $agentId,
            'user_id' => $userId,
            'sender' => 'human',
            'topic' => $topic,
            // 'grade_level' => $gradeLevel,
            'parameter_inputs' => implode(',', array_map(fn($pi) => $pi->id, $parameterInputIds)),
            'message_id' => 0,
        ]);

        $human->update(['message_id' => $messageId ?? $human->id]);
        return $human;
    }

    protected function buildPriorConversation($messageId)
    {
        return Message::where('message_id', $messageId)
            ->orderBy('created_at')
            ->pluck('topic')
            ->implode("\n");
    }

    protected function sendToBackendAPI(array $multipartData, string $endpoint)
    {
        return Http::timeout(0)->asMultipart()->post($endpoint, $multipartData);
    }

    protected function createAIMessage($responseText, $agentId, $gradeLevel, $parameterInputIds, $messageId)
    {
        return Message::create([
            'user_id' => Auth::id(),
            'agent_id' => $agentId,
            'sender' => 'ai',
            'topic' => $responseText,
            // 'grade_level' => $gradeLevel,
            'parameter_inputs' => implode(',', array_map(fn($pi) => $pi->id, $parameterInputIds)),
            'message_id' => $messageId,
        ]);
    }
}
