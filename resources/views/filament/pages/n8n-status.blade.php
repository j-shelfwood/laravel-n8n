<x-filament-panels::page>
    {{-- Connection Status --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-filament::icon icon="heroicon-o-bolt" class="h-8 w-8 text-gray-400" />
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">n8n Connection</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ config('n8n.api.url') ?: 'Not configured' }}</p>
                    @if (! $isConnected && $connectionError)
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $connectionError }}</p>
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
    </div>

    {{-- Events → Workflows --}}
    <div class="mt-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Event → Workflow Mapping</h2>

        @forelse ($events as $event)
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
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
                    <div class="mt-3 space-y-1 border-t border-gray-200 pt-3 dark:border-white/10">
                        @foreach ($event['workflows'] as $workflow)
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800">
                                <span class="text-gray-700 dark:text-gray-300">{{ $workflow['name'] }}</span>
                                <a href="{{ $workflow['url'] }}" target="_blank" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    View in n8n
                                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3.5 w-3.5" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <x-filament::icon icon="heroicon-o-bolt" class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-4 text-gray-500 dark:text-gray-400">No events with n8n triggers found.</p>
            </div>
        @endforelse
    </div>

    @if (! $isConnected)
        <div class="mt-6 rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-gray-600 dark:text-gray-400">Configure <code class="rounded bg-gray-100 px-1.5 py-0.5 text-sm dark:bg-gray-800">N8N_URL</code> and <code class="rounded bg-gray-100 px-1.5 py-0.5 text-sm dark:bg-gray-800">N8N_API_KEY</code> in your .env to enable the integration.</p>
            <x-filament::button wire:click="$refresh" color="primary" icon="heroicon-o-arrow-path" class="mt-4">
                Retry Connection
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
