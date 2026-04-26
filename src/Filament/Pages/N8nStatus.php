<?php

namespace Shelfwood\N8n\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Shelfwood\N8n\Services\N8nService;
use Shelfwood\N8n\Support\Psr4ClassResolver;
use Shelfwood\N8n\Traits\HasN8nTrigger;

/**
 * Filament page showing n8n integration status: connection health, event→workflow mapping.
 *
 * Scans directories configured in n8n.event_directories for event classes
 * using the HasN8nTrigger trait and matches their tags against active n8n workflows.
 */
class N8nStatus extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected string $view = 'n8n::filament.pages.n8n-status';

    protected static ?string $title = 'n8n Integration';

    protected static ?int $navigationSort = 99;

    public bool $isConnected = false;

    /** @var array<int, array<string, mixed>> */
    public array $events = [];

    /** @var array<string, mixed> */
    public array $workflows = [];

    public string $connectionError = '';

    /**
     * Active n8n workflows whose tags do not match any discovered event class.
     * Surfaced separately so operators can spot drift after a rename or after
     * deleting an event class without disabling the upstream n8n workflow.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $orphanedWorkflows = [];

    /**
     * Return the navigation group for the sidebar.
     */
    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Operations';
    }

    /**
     * Load connection status and match events to workflows on mount.
     */
    public function mount(N8nService $n8nService): void
    {
        try {
            $this->workflows = collect($n8nService->getWorkflows())->keyBy('id')->all();
            $this->isConnected = true;
        } catch (\Throwable $e) {
            $this->isConnected = false;
            $this->connectionError = $e->getMessage();
        }

        $this->events = $this->discoverEvents();
        $this->matchEventsToWorkflows();
        $this->collectOrphanedWorkflows();
    }

    /**
     * Identify active workflows whose tags carry the configured prefix but
     * match no discovered event class. Catches drift after an event rename
     * or deletion left an upstream workflow listening to a tag nothing fires.
     *
     * Restricts to workflows with at least one tag starting with the package
     * prefix so unrelated workflows (manually-tagged ones) are not flagged.
     */
    private function collectOrphanedWorkflows(): void
    {
        if (! $this->isConnected) {
            return;
        }

        $eventTags = [];
        foreach ($this->events as $event) {
            foreach ($event['tags'] as $tag) {
                $eventTags[$tag] = true;
            }
        }

        $prefix = (string) config('n8n.tag_prefix', 'app:');
        $orphaned = [];

        foreach ($this->workflows as $workflow) {
            if (! ($workflow['active'] ?? false)) {
                continue;
            }

            $workflowTags = array_map(
                fn (mixed $tag): string => is_array($tag) ? (string) ($tag['name'] ?? '') : (string) $tag,
                $workflow['tags'] ?? []
            );

            $appTags = array_values(array_filter(
                $workflowTags,
                fn (string $tag): bool => $tag !== '' && str_starts_with($tag, $prefix),
            ));

            if ($appTags === []) {
                continue;
            }

            $unmatched = array_values(array_diff($appTags, array_keys($eventTags)));

            if ($unmatched === []) {
                continue;
            }

            $orphaned[] = [
                'id' => $workflow['id'] ?? null,
                'name' => $workflow['name'] ?? '(unnamed)',
                'unmatched_tags' => $unmatched,
                'url' => rtrim(config('n8n.api.url'), '/').'/workflow/'.($workflow['id'] ?? ''),
            ];
        }

        $this->orphanedWorkflows = $orphaned;
    }

    /**
     * Scan configured event directories for classes using HasN8nTrigger.
     *
     * @return array<int, array<string, mixed>>
     */
    private function discoverEvents(): array
    {
        $events = [];
        $patterns = config('n8n.event_directories', ['app/Events']);

        foreach ($patterns as $pattern) {
            $dirs = glob(base_path($pattern));

            if ($dirs === false) {
                continue;
            }

            foreach ($dirs as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }

                foreach (File::allFiles($dir) as $file) {
                    $class = $this->resolveClassName($file->getPathname());

                    if ($class === null || ! class_exists($class)) {
                        continue;
                    }

                    $reflection = new ReflectionClass($class);

                    if (! in_array(HasN8nTrigger::class, $reflection->getTraitNames(), true)) {
                        continue;
                    }

                    $events[] = [
                        'name' => $reflection->getShortName(),
                        'class' => $class,
                        'tags' => HasN8nTrigger::generateDefaultN8nTagsForClass($class),
                        'workflows' => [],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Resolve a fully qualified class name from a file path.
     */
    private function resolveClassName(string $path): ?string
    {
        return Psr4ClassResolver::resolve($path, base_path());
    }

    /**
     * Match discovered events to active n8n workflows by tag intersection.
     */
    private function matchEventsToWorkflows(): void
    {
        if (! $this->isConnected) {
            return;
        }

        foreach ($this->events as &$event) {
            foreach ($this->workflows as $workflow) {
                if (! ($workflow['active'] ?? false)) {
                    continue;
                }

                $workflowTags = array_map(
                    fn (mixed $tag): string => is_array($tag) ? (string) ($tag['name'] ?? '') : (string) $tag,
                    $workflow['tags'] ?? []
                );

                if (empty(array_intersect($event['tags'], $workflowTags))) {
                    continue;
                }

                $event['workflows'][] = [
                    'id' => $workflow['id'],
                    'name' => $workflow['name'],
                    'url' => rtrim(config('n8n.api.url'), '/').'/workflow/'.$workflow['id'],
                ];
            }
        }
    }
}
