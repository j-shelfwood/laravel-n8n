<?php

namespace Shelfwood\N8n\Traits;

use Illuminate\Support\Str;

/**
 * Marks a Laravel Event as n8n-aware so the wildcard listener auto-dispatches it.
 *
 * Events using this trait are automatically tagged from their class name
 * (e.g. RedesignCompleted → app:redesign-completed) and carry a structured
 * payload for the n8n webhook. Tags can be overridden per-instance.
 */
trait HasN8nTrigger
{
    /** @var array<int, string> */
    protected array $n8nTags = [];

    protected bool $n8nTagsExplicitlySet = false;

    /**
     * Get the n8n tags for this event.
     *
     * @return array<int, string>
     */
    public function getN8nTags(): array
    {
        if ($this->n8nTagsExplicitlySet) {
            return $this->n8nTags;
        }

        return $this->generateDefaultN8nTags();
    }

    /**
     * Set custom tags for this event instance.
     *
     * @param  array<int, string>  $tags
     */
    public function setN8nTags(array $tags): static
    {
        $this->n8nTags = $tags;
        $this->n8nTagsExplicitlySet = true;

        return $this;
    }

    /**
     * Generate a convention-based tag from the class name.
     *
     * @return array<int, string>
     */
    protected function generateDefaultN8nTags(): array
    {
        return [self::tagPrefix().Str::kebab(class_basename($this))];
    }

    /**
     * Generate default tags for a class by name (used by the status page scanner).
     *
     * @return array<int, string>
     */
    public static function generateDefaultN8nTagsForClass(string $class): array
    {
        return [self::tagPrefix().Str::kebab(class_basename($class))];
    }

    /**
     * Resolve the configured tag prefix.
     *
     * Defaults to `app:` when the package config is unavailable so events
     * created outside a Laravel container (e.g. plain unit tests) still
     * generate stable tags.
     */
    private static function tagPrefix(): string
    {
        if (! function_exists('config')) {
            return 'app:';
        }

        $prefix = config('n8n.tag_prefix', 'app:');

        return is_string($prefix) ? $prefix : 'app:';
    }

    /**
     * Check if this event has n8n integration enabled.
     */
    public function hasN8nTrigger(): bool
    {
        return ! empty($this->getN8nTags());
    }

    /**
     * Build the full webhook payload including metadata.
     *
     * @return array<string, mixed>
     */
    public function getN8nPayload(): array
    {
        return [
            'event' => get_class($this),
            'timestamp' => now()->toIso8601String(),
            'tags' => $this->getN8nTags(),
            'data' => $this->toArray(),
        ];
    }

    /**
     * Serialize event data for the webhook payload.
     *
     * Override in each event class with the actual payload fields.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [];
    }
}
