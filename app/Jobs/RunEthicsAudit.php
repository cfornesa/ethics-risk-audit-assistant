<?php

namespace App\Jobs;

use App\Models\Item;
use App\Notifications\HighRiskItemDetected;
use App\Services\MistralClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RunEthicsAudit implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Item $item
    ) {
        $this->onQueue(config('ethics.queue.name', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(MistralClient $mistral): void
    {
        Log::info('Starting ethics audit', [
            'item_id' => $this->item->id,
            'project_id' => $this->item->project_id,
            'attempt' => $this->item->audit_attempts + 1,
        ]);

        try {
            $this->item->markAsProcessing();

            $result = $mistral->ethicsAudit(
                $this->item->content,
                $this->item->content_type
            );

            $this->validateAuditResult($result);

            $this->updateItemWithResults($result);

            $this->item->markAsCompleted();

            if ($this->shouldRequireHumanReview($result)) {
                $this->item->update(['requires_human_review' => true]);
            }

            if ($this->shouldSendNotification($result)) {
                $this->sendHighRiskNotification();
            }

            Log::info('Ethics audit completed successfully', [
                'item_id' => $this->item->id,
                'risk_score' => $result['risk_score'],
                'risk_level' => $result['risk_level'],
            ]);

        } catch (\Exception $e) {
            Log::error('Ethics audit failed', [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->item->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    protected function validateAuditResult(array $result): void
    {
        $required = ['risk_score', 'risk_level', 'risk_summary', 'risk_breakdown', 'mitigation_suggestions'];

        foreach ($required as $field) {
            if (!isset($result[$field])) {
                throw new \RuntimeException("Missing required field in audit result: {$field}");
            }
        }

        if (!in_array($result['risk_level'], ['low', 'medium', 'high', 'critical'])) {
            throw new \RuntimeException("Invalid risk level: {$result['risk_level']}");
        }

        if ($result['risk_score'] < 0 || $result['risk_score'] > 100) {
            throw new \RuntimeException("Invalid risk score: {$result['risk_score']}");
        }
    }

    protected function updateItemWithResults(array $result): void
    {
        $this->item->update([
            'risk_score' => $result['risk_score'],
            'risk_level' => $result['risk_level'],
            'risk_summary' => $result['risk_summary'],
            'risk_breakdown' => $result['risk_breakdown'],
            'mitigation_suggestions' => $result['mitigation_suggestions'] ?? [],
            'llm_raw_response' => json_encode($result),
            'llm_model' => config('services.mistral.model'),
        ]);
    }

    protected function shouldRequireHumanReview(array $result): bool
    {
        if ($result['risk_score'] > config('ethics.auto_human_review_threshold', 50)) {
            return true;
        }

        if (isset($result['requires_human_review']) && $result['requires_human_review']) {
            return true;
        }

        $threshold = config('ethics.category_high_score_threshold', 8);
        foreach ($result['risk_breakdown'] as $category => $data) {
            if (isset($data['score']) && $data['score'] >= $threshold) {
                return true;
            }
        }

        return false;
    }

    protected function shouldSendNotification(array $result): bool
    {
        if (!config('ethics.notifications.enabled', true)) {
            return false;
        }

        if ($this->item->notification_sent) {
            return false;
        }

        $notifyThreshold = config('ethics.auto_notify_threshold', 51);

        return $result['risk_score'] >= $notifyThreshold;
    }

    protected function sendHighRiskNotification(): void
    {
        $recipients = config('ethics.notifications.recipients', []);

        if (empty($recipients)) {
            Log::warning('No notification recipients configured');
            return;
        }

        Notification::route('mail', $recipients)
            ->notify(new HighRiskItemDetected($this->item));

        $this->item->update(['notification_sent' => true]);

        Log::info('High risk notification sent', [
            'item_id' => $this->item->id,
            'recipients' => $recipients,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Ethics audit job failed permanently', [
            'item_id' => $this->item->id,
            'error' => $exception->getMessage(),
        ]);

        $this->item->markAsFailed($exception->getMessage());
    }
}
