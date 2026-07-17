<?php

namespace App\Services;

use App\Ai\Agents\PropertyDescriptionAgent;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Enums\Lab;

/**
 * PropertyDescriptionService
 *
 * Orchestrates the AI description generation for a Unit using the
 * same proven pattern as ResumeAnalysisService:
 *   ->prompt() on the agent instance with Lab::OpenRouter.
 *
 * Saving is intentionally separate from generating so the Filament
 * action can show a preview modal — the user must explicitly approve
 * before anything is written to the database.
 */
class PropertyDescriptionService
{
    /**
     * Generate a description for the given unit using AI.
     *
     * @param  Unit  $unit
     * @return string  The AI-generated description (plain text, no Markdown).
     */
    public function generate(Unit $unit): string
    {
        $unit->loadMissing(['property', 'features']);

        $prompt = $this->buildPrompt($unit);

        // Exact same call pattern that works in ResumeAnalysisService ✓
        $response = (new PropertyDescriptionAgent)->prompt(
            $prompt,
            [],
            Lab::OpenRouter,
            'openrouter/free',
            60,
        );

        \Log::info('[PropertyDescriptionService] AI response', ['text' => $response->text]);

        return trim((string) $response->text);
    }

    /**
     * Persist the AI-generated description to the unit inside a DB transaction.
     * If the user cancels the modal, this method is never called, so the
     * existing description is preserved automatically.
     */
    public function saveDescription(Unit $unit, string $description): void
    {
        DB::transaction(function () use ($unit, $description) {
            $unit->update(['description' => $description]);
        });
    }

    /**
     * Build a structured, human-readable prompt from the unit's attributes
     * and its related features/amenities.
     */
    private function buildPrompt(Unit $unit): string
    {
        $lines = [];

        $lines[] = 'Please write a property description for the following unit:';
        $lines[] = '';

        if ($unit->property?->name) {
            $lines[] = "Property: {$unit->property->name}";
        }
        if ($unit->property?->address) {
            $lines[] = "Location: {$unit->property->address}";
        }
        if ($unit->type) {
            $lines[] = 'Type: ' . ucfirst($unit->type);
        }
        if ($unit->unit_number) {
            $lines[] = "Unit Number: {$unit->unit_number}";
        }
        if ($unit->bedrooms !== null) {
            $lines[] = "Bedrooms: {$unit->bedrooms}";
        }
        if ($unit->bathrooms !== null) {
            $lines[] = "Bathrooms: {$unit->bathrooms}";
        }
        if ($unit->sqft !== null) {
            $lines[] = "Area: {$unit->sqft} sqft";
        }
        if ($unit->rent_price !== null) {
            $lines[] = 'Monthly Rent: $' . number_format((float) $unit->rent_price, 2);
        }

        // Only include features that are actually enabled (not false/0/empty)
        $features = $unit->features
            ->filter(fn ($f) => ! in_array(
                strtolower((string) ($f->value ?? '')),
                ['false', '0', ''],
                true
            ))
            ->map(fn ($f) => $f->name)
            ->filter()
            ->values();

        if ($features->isNotEmpty()) {
            $lines[] = 'Amenities & Features: ' . $features->implode(', ');
        }

        return implode("\n", $lines);
    }
}

