<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $riskLevel = fake()->randomElement(['low', 'medium', 'high', 'critical']);
        $riskScore = match($riskLevel) {
            'low' => fake()->numberBetween(0, 25),
            'medium' => fake()->numberBetween(26, 50),
            'high' => fake()->numberBetween(51, 75),
            'critical' => fake()->numberBetween(76, 100),
        };

        return [
            'project_id' => \App\Models\Project::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'content_type' => fake()->randomElement(['message', 'ad', 'script', 'post', 'other']),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'requires_review']),
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_summary' => fake()->paragraph(),
            'risk_breakdown' => [
                'microtargeting' => ['score' => fake()->numberBetween(0, 10), 'issues' => fake()->words(3)],
                'emotional_manipulation' => ['score' => fake()->numberBetween(0, 10), 'issues' => fake()->words(3)],
                'disinformation' => ['score' => fake()->numberBetween(0, 10), 'issues' => fake()->words(3)],
            ],
            'mitigation_suggestions' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
            'llm_raw_response' => json_encode(['test' => 'response']),
            'llm_model' => 'ministral-3-14b-reasoning-2512',
            'audited_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'requires_human_review' => fake()->boolean(20),
            'notification_sent' => fake()->boolean(50),
            'audit_attempts' => fake()->numberBetween(0, 3),
            'last_error' => null,
            'metadata' => [
                'source' => fake()->randomElement(['web', 'api', 'import']),
            ],
        ];
    }
}
