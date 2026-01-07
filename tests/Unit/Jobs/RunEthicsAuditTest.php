<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RunEthicsAudit;
use App\Models\Item;
use App\Models\Project;
use App\Notifications\HighRiskItemDetected;
use App\Services\MistralClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RunEthicsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ethics.auto_human_review_threshold', 50);
        Config::set('ethics.auto_notify_threshold', 51);
        Config::set('ethics.category_high_score_threshold', 8);
        Config::set('ethics.notifications.enabled', true);
        Config::set('ethics.notifications.recipients', ['admin@example.com']);
    }

    public function test_successful_audit_updates_item_with_results(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'content' => 'Test political content',
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 25,
            'risk_level' => 'low',
            'risk_summary' => 'No significant risks',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 3, 'issues' => []],
                'emotional_manipulation' => ['score' => 4, 'issues' => []],
                'disinformation' => ['score' => 3, 'issues' => []],
                'voter_suppression' => ['score' => 4, 'issues' => []],
                'vulnerable_populations' => ['score' => 3, 'issues' => []],
                'ai_transparency' => ['score' => 4, 'issues' => []],
                'legal_regulatory' => ['score' => 4, 'issues' => []],
            ],
            'mitigation_suggestions' => ['Review content'],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')
            ->once()
            ->with($item->content, $item->content_type)
            ->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        $item->refresh();
        $this->assertEquals('completed', $item->status);
        $this->assertEquals(25, $item->risk_score);
        $this->assertEquals('low', $item->risk_level);
        $this->assertEquals('No significant risks', $item->risk_summary);
        $this->assertFalse($item->requires_human_review);
        $this->assertNotNull($item->audited_at);
    }

    public function test_high_risk_score_triggers_human_review(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 60,
            'risk_level' => 'high',
            'risk_summary' => 'High risk detected',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 8, 'issues' => []],
                'emotional_manipulation' => ['score' => 9, 'issues' => []],
                'disinformation' => ['score' => 9, 'issues' => []],
                'voter_suppression' => ['score' => 8, 'issues' => []],
                'vulnerable_populations' => ['score' => 8, 'issues' => []],
                'ai_transparency' => ['score' => 9, 'issues' => []],
                'legal_regulatory' => ['score' => 9, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        $item->refresh();
        $this->assertTrue($item->requires_human_review);
    }

    public function test_high_category_score_triggers_human_review(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 30,
            'risk_level' => 'medium',
            'risk_summary' => 'Medium risk',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 2, 'issues' => []],
                'emotional_manipulation' => ['score' => 2, 'issues' => []],
                'disinformation' => ['score' => 9, 'issues' => ['High disinformation risk']],
                'voter_suppression' => ['score' => 3, 'issues' => []],
                'vulnerable_populations' => ['score' => 4, 'issues' => []],
                'ai_transparency' => ['score' => 5, 'issues' => []],
                'legal_regulatory' => ['score' => 5, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        $item->refresh();
        $this->assertTrue($item->requires_human_review);
    }

    public function test_audit_result_requires_human_review_flag_is_respected(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 20,
            'risk_level' => 'low',
            'risk_summary' => 'Low risk',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 3, 'issues' => []],
                'emotional_manipulation' => ['score' => 3, 'issues' => []],
                'disinformation' => ['score' => 3, 'issues' => []],
                'voter_suppression' => ['score' => 3, 'issues' => []],
                'vulnerable_populations' => ['score' => 3, 'issues' => []],
                'ai_transparency' => ['score' => 3, 'issues' => []],
                'legal_regulatory' => ['score' => 2, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => true,
            'flags' => ['Requires manual review'],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        $item->refresh();
        $this->assertTrue($item->requires_human_review);
    }

    public function test_high_risk_score_sends_notification(): void
    {
        Notification::fake();

        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
            'notification_sent' => false,
        ]);

        $mockResult = [
            'risk_score' => 60,
            'risk_level' => 'high',
            'risk_summary' => 'High risk',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 8, 'issues' => []],
                'emotional_manipulation' => ['score' => 9, 'issues' => []],
                'disinformation' => ['score' => 9, 'issues' => []],
                'voter_suppression' => ['score' => 8, 'issues' => []],
                'vulnerable_populations' => ['score' => 8, 'issues' => []],
                'ai_transparency' => ['score' => 9, 'issues' => []],
                'legal_regulatory' => ['score' => 9, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        Notification::assertSentTo(
            Notification::route('mail', ['admin@example.com']),
            HighRiskItemDetected::class
        );

        $item->refresh();
        $this->assertTrue($item->notification_sent);
    }

    public function test_notification_not_sent_below_threshold(): void
    {
        Notification::fake();

        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 40,
            'risk_level' => 'medium',
            'risk_summary' => 'Medium risk',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 5, 'issues' => []],
                'emotional_manipulation' => ['score' => 6, 'issues' => []],
                'disinformation' => ['score' => 6, 'issues' => []],
                'voter_suppression' => ['score' => 5, 'issues' => []],
                'vulnerable_populations' => ['score' => 6, 'issues' => []],
                'ai_transparency' => ['score' => 6, 'issues' => []],
                'legal_regulatory' => ['score' => 6, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        Notification::assertNothingSent();

        $item->refresh();
        $this->assertFalse($item->notification_sent);
    }

    public function test_notification_not_sent_if_already_sent(): void
    {
        Notification::fake();

        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
            'notification_sent' => true,
        ]);

        $mockResult = [
            'risk_score' => 60,
            'risk_level' => 'high',
            'risk_summary' => 'High risk',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 8, 'issues' => []],
                'emotional_manipulation' => ['score' => 9, 'issues' => []],
                'disinformation' => ['score' => 9, 'issues' => []],
                'voter_suppression' => ['score' => 8, 'issues' => []],
                'vulnerable_populations' => ['score' => 8, 'issues' => []],
                'ai_transparency' => ['score' => 9, 'issues' => []],
                'legal_regulatory' => ['score' => 9, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        Notification::assertNothingSent();
    }

    public function test_notification_disabled_in_config(): void
    {
        Config::set('ethics.notifications.enabled', false);
        Notification::fake();

        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 60,
            'risk_level' => 'high',
            'risk_summary' => 'High risk',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 8, 'issues' => []],
                'emotional_manipulation' => ['score' => 9, 'issues' => []],
                'disinformation' => ['score' => 9, 'issues' => []],
                'voter_suppression' => ['score' => 8, 'issues' => []],
                'vulnerable_populations' => ['score' => 8, 'issues' => []],
                'ai_transparency' => ['score' => 9, 'issues' => []],
                'legal_regulatory' => ['score' => 9, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        Notification::assertNothingSent();
    }

    public function test_failed_audit_throws_exception(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')
            ->andThrow(new \RuntimeException('API Error'));

        $this->expectException(\RuntimeException::class);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        $item->refresh();
        $this->assertEquals('failed', $item->status);
        $this->assertNotNull($item->last_error);
    }

    public function test_failed_audit_marks_item_as_failed(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')
            ->andThrow(new \RuntimeException('API Error'));

        try {
            $job = new RunEthicsAudit($item);
            $job->handle($mockMistral);
        } catch (\Exception $e) {
            // Expected
        }

        $item->refresh();
        $this->assertEquals('failed', $item->status);
    }

    public function test_invalid_audit_result_throws_exception(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 25,
            // Missing required fields
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required field');

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);
    }

    public function test_invalid_risk_level_throws_exception(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 25,
            'risk_level' => 'invalid',
            'risk_summary' => 'Test',
            'risk_breakdown' => [],
            'mitigation_suggestions' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid risk level');

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);
    }

    public function test_invalid_risk_score_throws_exception(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $mockResult = [
            'risk_score' => 150,
            'risk_level' => 'critical',
            'risk_summary' => 'Test',
            'risk_breakdown' => [],
            'mitigation_suggestions' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid risk score');

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);
    }

    public function test_failed_method_logs_error(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Ethics audit job failed permanently', \Mockery::on(function ($data) {
                return isset($data['item_id']) && isset($data['error']);
            }));

        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $job = new RunEthicsAudit($item);
        $job->failed(new \Exception('Test exception'));

        $item->refresh();
        $this->assertEquals('failed', $item->status);
    }

    public function test_audit_increments_attempt_counter(): void
    {
        $project = Project::factory()->create();
        $item = Item::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
            'audit_attempts' => 0,
        ]);

        $mockResult = [
            'risk_score' => 25,
            'risk_level' => 'low',
            'risk_summary' => 'No significant risks',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 3, 'issues' => []],
                'emotional_manipulation' => ['score' => 4, 'issues' => []],
                'disinformation' => ['score' => 3, 'issues' => []],
                'voter_suppression' => ['score' => 4, 'issues' => []],
                'vulnerable_populations' => ['score' => 3, 'issues' => []],
                'ai_transparency' => ['score' => 4, 'issues' => []],
                'legal_regulatory' => ['score' => 4, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        $mockMistral = $this->mock(MistralClient::class);
        $mockMistral->shouldReceive('ethicsAudit')->andReturn($mockResult);

        $job = new RunEthicsAudit($item);
        $job->handle($mockMistral);

        $item->refresh();
        $this->assertEquals(1, $item->audit_attempts);
    }
}
