<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('content_type', ['message', 'ad', 'script', 'post', 'other'])->default('message');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'requires_review'])->default('pending');

            // Risk Assessment Fields
            $table->integer('risk_score')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->text('risk_summary')->nullable();
            $table->json('risk_breakdown')->nullable();
            $table->json('mitigation_suggestions')->nullable();

            // LLM Response
            $table->longText('llm_raw_response')->nullable();
            $table->string('llm_model')->nullable();
            $table->timestamp('audited_at')->nullable();

            // Flags
            $table->boolean('requires_human_review')->default(false);
            $table->boolean('notification_sent')->default(false);

            // Audit Trail
            $table->integer('audit_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('status');
            $table->index('risk_level');
            $table->index('requires_human_review');
            $table->index('audited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
