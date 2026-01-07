<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Risk Threshold Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the risk score thresholds for different risk levels and
    | determine when automated actions should be triggered.
    |
    */

    'risk_thresholds' => [
        'low' => [
            'min' => 0,
            'max' => 25,
        ],
        'medium' => [
            'min' => 26,
            'max' => 50,
        ],
        'high' => [
            'min' => 51,
            'max' => 75,
        ],
        'critical' => [
            'min' => 76,
            'max' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated Actions
    |--------------------------------------------------------------------------
    |
    | Configure when automated actions like notifications and human review
    | requirements should be triggered.
    |
    */

    'auto_human_review_threshold' => 50,
    'auto_notify_threshold' => 51,
    'category_high_score_threshold' => 8,

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notification recipients and settings for high-risk items.
    |
    */

    'notifications' => [
        'enabled' => env('ETHICS_NOTIFICATIONS_ENABLED', true),
        'recipients' => [
            env('ETHICS_NOTIFICATION_EMAIL', 'admin@example.com'),
        ],
        'levels' => ['high', 'critical'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for ethics audits.
    |
    */

    'queue' => [
        'connection' => env('ETHICS_QUEUE_CONNECTION', 'database'),
        'name' => env('ETHICS_QUEUE_NAME', 'default'),
        'retry_attempts' => 3,
        'retry_delay' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Types
    |--------------------------------------------------------------------------
    |
    | Define the available content types for items.
    |
    */

    'content_types' => [
        'message' => 'Message',
        'ad' => 'Advertisement',
        'script' => 'Script',
        'post' => 'Social Media Post',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Categories
    |--------------------------------------------------------------------------
    |
    | Define the risk categories used in the ethics rubric.
    |
    */

    'risk_categories' => [
        'microtargeting' => 'Microtargeting',
        'emotional_manipulation' => 'Emotional Manipulation',
        'disinformation' => 'Disinformation',
        'voter_suppression' => 'Voter Suppression',
        'vulnerable_populations' => 'Vulnerable Populations',
        'ai_transparency' => 'AI/Transparency',
        'legal_regulatory' => 'Legal/Regulatory',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ethics Rubric System Prompt
    |--------------------------------------------------------------------------
    |
    | The system prompt used by the AI to evaluate content for ethics risks.
    | This can be customized to adjust the evaluation criteria and scoring.
    |
    */

    'rubric_system_prompt' => <<<'PROMPT'
You are an expert ethics auditor for political communications. Your task is to analyze political content (messages, ads, scripts, posts) for ethical risks and regulatory compliance.

Use the following rubric to evaluate content:

1. MICROTARGETING (0-10)
   - Exploits personal data or psychological profiles
   - Targets vulnerable demographic segments
   - Uses covert personalization strategies

2. EMOTIONAL MANIPULATION (0-10)
   - Fear-mongering or panic induction
   - Exploitation of grief, anger, or outrage
   - Misleading emotional appeals

3. DISINFORMATION (0-10)
   - False or misleading claims
   - Lack of source attribution
   - Context manipulation or deepfakes

4. VOTER SUPPRESSION (0-10)
   - Discourages voting participation
   - Spreads false voting information
   - Targets specific groups to reduce turnout

5. VULNERABLE POPULATIONS (0-10)
   - Exploits children, elderly, or disadvantaged groups
   - Preys on lack of media literacy
   - Uses confusing or deceptive language

6. AI/TRANSPARENCY (0-10)
   - Fails to disclose AI-generated content
   - Uses synthetic media without labeling
   - Lacks clear sponsorship information

7. LEGAL/REGULATORY (0-10)
   - Election law violations
   - Privacy regulation breaches
   - Platform policy violations

RESPONSE FORMAT (JSON):
{
  "risk_score": 0-100,
  "risk_level": "low|medium|high|critical",
  "risk_summary": "Brief overall assessment",
  "risk_breakdown": {
    "microtargeting": {"score": 0-10, "issues": ["list of specific concerns"]},
    "emotional_manipulation": {"score": 0-10, "issues": ["list of specific concerns"]},
    "disinformation": {"score": 0-10, "issues": ["list of specific concerns"]},
    "voter_suppression": {"score": 0-10, "issues": ["list of specific concerns"]},
    "vulnerable_populations": {"score": 0-10, "issues": ["list of specific concerns"]},
    "ai_transparency": {"score": 0-10, "issues": ["list of specific concerns"]},
    "legal_regulatory": {"score": 0-10, "issues": ["list of specific concerns"]}
  },
  "mitigation_suggestions": ["actionable recommendations"],
  "requires_human_review": boolean,
  "flags": ["list of critical red flags"]
}

Calculate risk_score as the sum of all category scores. Determine risk_level:
- low: 0-25
- medium: 26-50
- high: 51-75
- critical: 76-100

Set requires_human_review to true if risk_score > 50 or if any category scores >= 8.

Always respond with valid JSON only.
PROMPT,

];
