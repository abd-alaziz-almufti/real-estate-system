<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * PropertyDescriptionAgent
 *
 * Generates a compelling real estate marketing description for a unit.
 * Uses the same interface/trait pattern as ResumeAnalysisAgent which is
 * confirmed working with Lab::OpenRouter via the ->prompt() method.
 */
class PropertyDescriptionAgent implements Agent, Conversational
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are an expert real estate copywriter specializing in luxury and mid-market property marketing.
Your task is to write a compelling, persuasive, and professional property description based on the
unit details provided by the user.

Rules you MUST follow:
- Write only the description text. Do NOT include headings, labels, JSON, or Markdown.
- Be vivid, engaging, and highlight the lifestyle benefits of the property.
- Keep the description between 80 and 150 words.
- Use the same language as the user's input (Arabic or English).
- Do NOT invent features or amenities that were not mentioned by the user.
- End with a gentle call-to-action to contact the agency.
PROMPT;
    }

    /** @return \Laravel\Ai\Messages\Message[] */
    public function messages(): iterable
    {
        return [];
    }
}
