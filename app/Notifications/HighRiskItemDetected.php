<?php

namespace App\Notifications;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HighRiskItemDetected extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Item $item
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $riskLevelColors = [
            'critical' => '#ef4444',
            'high' => '#f97316',
            'medium' => '#f59e0b',
            'low' => '#10b981',
        ];

        $color = $riskLevelColors[$this->item->risk_level] ?? '#6b7280';

        $message = (new MailMessage)
            ->subject("⚠️ High Risk Content Detected: {$this->item->title}")
            ->greeting('High Risk Content Alert')
            ->line("A **{$this->item->risk_level}** risk item has been detected and requires your attention.")
            ->line('')
            ->line("**Project:** {$this->item->project->name}")
            ->line("**Item:** {$this->item->title}")
            ->line("**Risk Score:** {$this->item->risk_score}/100")
            ->line("**Risk Level:** " . strtoupper($this->item->risk_level))
            ->line('')
            ->line("**Risk Summary:**")
            ->line($this->item->risk_summary)
            ->line('')
            ->action('Review Item', url("/items/{$this->item->id}"))
            ->line('Please review this content as soon as possible.');

        if ($this->item->requires_human_review) {
            $message->line('')
                ->line('⚠️ **This item requires human review before proceeding.**');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'item_id' => $this->item->id,
            'project_id' => $this->item->project_id,
            'risk_score' => $this->item->risk_score,
            'risk_level' => $this->item->risk_level,
            'title' => $this->item->title,
            'requires_review' => $this->item->requires_human_review,
        ];
    }
}
