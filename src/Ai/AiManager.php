<?php

namespace ApiHub\Laravel\Ai;

use ApiHub\Laravel\Ai\Drivers\AnthropicDriver;
use ApiHub\Laravel\Ai\Drivers\DeepSeekDriver;
use ApiHub\Laravel\Ai\Drivers\GeminiDriver;
use ApiHub\Laravel\Ai\Drivers\OpenAiDriver;
use ApiHub\Laravel\Contracts\AiProvider;
use ApiHub\Laravel\Http\Connector;
use Illuminate\Support\Manager;

/**
 * Resolves AI provider drivers from config('apihub.ai').
 *
 * @method \ApiHub\Laravel\Ai\DTO\ChatResponse chat(\ApiHub\Laravel\Ai\DTO\ChatRequest $request)
 */
class AiManager extends Manager
{
    protected ?FakeAi $fake = null;

    public function getDefaultDriver(): string
    {
        return (string) config('apihub.ai.default');
    }

    /**
     * Swap in an in-memory provider for testing. All resolution returns it
     * until the manager is rebuilt.
     */
    public function fake(string $reply = 'This is a fake AI response.'): FakeAi
    {
        return $this->fake = new FakeAi($reply);
    }

    public function driver($driver = null): mixed
    {
        return $this->fake ?? parent::driver($driver);
    }

    protected function createOpenaiDriver(): AiProvider
    {
        return new OpenAiDriver($this->connector(), $this->driverConfig('openai'));
    }

    protected function createAnthropicDriver(): AiProvider
    {
        return new AnthropicDriver($this->connector(), $this->driverConfig('anthropic'));
    }

    protected function createGeminiDriver(): AiProvider
    {
        return new GeminiDriver($this->connector(), $this->driverConfig('gemini'));
    }

    protected function createDeepseekDriver(): AiProvider
    {
        return new DeepSeekDriver($this->connector(), $this->driverConfig('deepseek'));
    }

    protected function connector(): Connector
    {
        return app(Connector::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function driverConfig(string $name): array
    {
        return (array) config("apihub.ai.drivers.{$name}", []);
    }
}
