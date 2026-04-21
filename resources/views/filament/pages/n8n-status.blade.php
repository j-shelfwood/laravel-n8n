<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        {{-- Connection Status --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <x-filament::icon icon="heroicon-o-bolt" class="fi-section-header-icon h-8 w-8" />
                    <div>
                        <h3 class="fi-section-header-heading">n8n Connection</h3>
                        <p class="fi-section-header-description">{{ config('n8n.api.url') ?: 'Not configured' }}</p>
                        @if (! $isConnected && $connectionError)
                            <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $connectionError }}</p>
                        @endif
                    </div>
                </div>
                @if ($isConnected)
                    <x-filament::badge color="success" icon="heroicon-o-check-circle">Connected</x-filament::badge>
                @elseif (config('n8n.api.url'))
                    <x-filament::badge color="danger" icon="heroicon-o-x-circle">Disconnected</x-filament::badge>
                @else
                    <x-filament::badge color="gray" icon="heroicon-o-minus-circle">Not configured</x-filament::badge>
                @endif
            </div>

            @if ($isConnected)
                <div class="mt-4 grid grid-cols-2 gap-4 border-t border-gray-200 pt-4 text-sm dark:border-white/10">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Active workflows:</span>
                        <span class="ml-2 text-gray-600 dark:text-gray-400">{{ collect($workflows)->where('active', true)->count() }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Events discovered:</span>
                        <span class="ml-2 text-gray-600 dark:text-gray-400">{{ count($events) }}</span>
                    </div>
                </div>
            @endif
        </x-filament::section>

        {{-- Events → Workflows --}}
        <x-filament::section>
            <x-slot name="heading">Event → Workflow Mapping</x-slot>

            <div class="flex flex-col gap-4">
                @forelse ($events as $event)
                    <x-filament::section :compact="true" :secondary="true">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ $event['name'] }}</h3>
                                <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $event['class'] }}</p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($event['tags'] as $tag)
                                        <x-filament::badge color="primary" size="sm">{{ $tag }}</x-filament::badge>
                                    @endforeach
                                </div>
                            </div>

                            <div class="shrink-0">
                                @if (! $isConnected)
                                    <x-filament::badge color="gray" size="sm">n8n offline</x-filament::badge>
                                @elseif (empty($event['workflows']))
                                    <x-filament::badge color="warning" size="sm">No workflow</x-filament::badge>
                                @else
                                    <x-filament::badge color="success" size="sm">{{ count($event['workflows']) }} connected</x-filament::badge>
                                @endif
                            </div>
                        </div>

                        @if (! empty($event['workflows']))
                            <div class="mt-3 flex flex-col gap-1 border-t border-gray-200 pt-3 dark:border-white/10">
                                @foreach ($event['workflows'] as $workflow)
                                    <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2 text-sm dark:bg-gray-900">
                                        <span class="text-gray-700 dark:text-gray-300">{{ $workflow['name'] }}</span>
                                        <x-filament::link
                                            :href="$workflow['url']"
                                            target="_blank"
                                            icon="heroicon-o-arrow-top-right-on-square"
                                            icon-position="after"
                                            size="sm"
                                        >
                                            View in n8n
                                        </x-filament::link>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-filament::section>
                @empty
                    <x-filament::section :compact="true">
                        <div class="text-center">
                            <x-filament::icon icon="heroicon-o-bolt" class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-4 text-gray-500 dark:text-gray-400">No events with n8n triggers found.</p>
                        </div>
                    </x-filament::section>
                @endforelse
            </div>
        </x-filament::section>

        @if (! $isConnected)
            <x-filament::section>
                <div class="text-center">
                    <p class="text-gray-600 dark:text-gray-400">Configure <code class="rounded bg-gray-100 px-1.5 py-0.5 text-sm dark:bg-gray-800">N8N_URL</code> and <code class="rounded bg-gray-100 px-1.5 py-0.5 text-sm dark:bg-gray-800">N8N_API_KEY</code> in your .env to enable the integration.</p>
                    <x-filament::button wire:click="$refresh" color="primary" icon="heroicon-o-arrow-path" class="mt-4">
                        Retry Connection
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
