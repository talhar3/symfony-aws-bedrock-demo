<?php

namespace App\Service;

use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;

class AwsBedrock
{
    private BedrockAgentRuntimeClient $bedrockAgentRuntimeClient;

    public function __construct(
        public string           $awsRegion,
        public string           $awsAccessKeyID,
        public string           $awsSecretAccessKey,
        private readonly string $bedrockAgentId,
        private readonly string $bedrockAgentAliasId,
    )
    {
        $this->bedrockAgentRuntimeClient = new BedrockAgentRuntimeClient([
            'region' => $awsRegion,
            'version' => 'latest',
            'credentials' => [
                'key' => $awsAccessKeyID,
                'secret' => $awsSecretAccessKey,
            ],
        ]);
    }

    public function askQuestion(string $question): string
    {
        // Ask the Bedrock Agent a question
        $result = $this->bedrockAgentRuntimeClient->invokeAgent([
            'agentId' => $this->bedrockAgentId,
            'agentAliasId' => $this->bedrockAgentAliasId,
            'sessionId' => uniqid(),
            'inputText' => $question,
        ]);

        // The answer is returned in chunks
        $answer = '';
        foreach ($result['completion'] as $event) {
            if (isset($event['chunk']['bytes'])) {
                $answer .= $event['chunk']['bytes'];
            }
        }
        return $answer;
    }
}
