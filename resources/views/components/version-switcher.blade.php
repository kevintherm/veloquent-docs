@php
    $releaseVersions = $availableVersions->filter(fn ($v) => ! str_starts_with($v, 'dev-'));
    $devVersions = $availableVersions->filter(fn ($v) => str_starts_with($v, 'dev-'));
    
    $buildVersionUrl = function($newVersion) use ($currentPath, $version, $headingAnchor) {
        $docsPath = config('docs.path', 'docs');
        
        if (!empty($currentPath)) {
            $url = "/{$docsPath}/{$newVersion}/{$currentPath}";
            if ($headingAnchor) {
                $url .= "?heading={$headingAnchor}";
            }
            return $url;
        }
        
        $query = request()->query();
        $query['version'] = $newVersion;
        $queryString = http_build_query($query);
        return route('docs.search') . ($queryString ? "?{$queryString}" : '');
    };
@endphp

<div class="flex items-center gap-2" x-data="{ open: false }">
    <label class="text-sm font-bold text-black/50 uppercase tracking-wide">Version:</label>
    
    <div class="relative">
        <button
            @click="open = !open"
            class="flex items-center gap-2 px-4 py-2 bg-slate-100 border-2 border-black/20 hover:border-blue-500 font-bold uppercase tracking-tight text-sm transition-all"
        >
            <span>{{ \App\Docs\DocsManager::class ? app(\App\Docs\DocsManager::class)->getVersionLabel($version) : $version }}</span>
            <svg class="w-4 h-4" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            @click.outside="open = false"
            class="absolute top-full left-0 mt-1 bg-white border-2 border-black shadow-lg z-50 min-w-max"
        >
            @if($releaseVersions->isNotEmpty())
                <div class="px-4 py-2 text-xs font-bold text-black/40 uppercase tracking-wider bg-slate-50 border-b border-black/10">
                    Releases
                </div>
                @foreach($releaseVersions as $v)
                    <a
                        href="{{ $buildVersionUrl($v) }}"
                        :click="open = false"
                        class="block px-4 py-3 hover:bg-blue-50 font-bold uppercase tracking-tight text-sm transition-colors border-b border-black/5 last:border-b-0
                        {{ $version === $v ? 'bg-blue-100 text-blue-900' : 'text-black' }}"
                    >
                        {{ app(\App\Docs\DocsManager::class)->getVersionLabel($v) }}
                    </a>
                @endforeach
            @endif

            @if($devVersions->isNotEmpty())
                <div class="px-4 py-2 text-xs font-bold text-black/40 uppercase tracking-wider bg-slate-50 border-b border-black/10">
                    Development
                </div>
                @foreach($devVersions as $v)
                    <a
                        href="{{ $buildVersionUrl($v) }}"
                        :click="open = false"
                        class="block px-4 py-3 hover:bg-blue-50 font-bold uppercase tracking-tight text-sm transition-colors border-b border-black/5 last:border-b-0 italic
                        {{ $version === $v ? 'bg-blue-100 text-blue-900' : 'text-black' }}"
                    >
                        {{ app(\App\Docs\DocsManager::class)->getVersionLabel($v) }}
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</div>
