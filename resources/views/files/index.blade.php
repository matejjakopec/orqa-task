<x-layouts.app :title="__($global ? 'files.all_files' : 'files.title')">
    <x-page-header :title="__($global ? 'files.all_files' : 'files.title')" />

    @if($canUpload)
        <div class="d-grid gap-3 mb-3">
            <div><x-files.upload /></div>
            <div><x-card :title="__('accounts.storage')"><x-storage-summary :account="$account" /></x-card></div>
        </div>
    @endif

    <x-card>
        <x-form :action="route('files.index')" method="GET" bag="fileFilters">
            @if(isset($filters['owner_id']))
                <input type="hidden" name="owner_id" value="{{ $filters['owner_id'] }}">
                <p class="text-body-secondary mb-0">{{ __('files.owner_filter') }} <a href="{{ route('files.index') }}">{{ __('accounts.clear_filters') }}</a></p>
            @endif
            <div class="d-grid gap-3">
                <div><x-form-input name="search" :label="__('files.search')" :value="$filters['search'] ?? null" :placeholder="__('files.search_hint')" maxlength="100" bag="fileFilters" /></div>
                @if($global)<div><x-form-select name="organisation_id" :label="__('ui.organisation')" :options="$organisations->mapWithKeys(fn (\App\Models\Organisation $item): array => [$item->id => $item->name.' · '.substr($item->public_id, -8)])->all()" :value="$filters['organisation_id'] ?? null" :placeholder="__('accounts.all_organisations')" bag="fileFilters" /></div>@endif
                <div class="d-grid"><x-button type="submit">{{ __('accounts.filter') }}</x-button></div>
            </div>
        </x-form>
        <hr class="my-3">
        @if($files->count())
            <x-files.table :files="$files" :global="$global" />
            <div class="mt-3">{{ $files->appends($filters)->links() }}</div>
        @elseif(collect($filters)->filter(fn (mixed $value): bool => $value !== null && $value !== '')->isNotEmpty())
            <x-empty-state :title="__('accounts.no_matches')"><x-slot:action><x-button :href="route('files.index')" variant="outline-secondary">{{ __('accounts.clear_filters') }}</x-button></x-slot:action></x-empty-state>
        @else
            <x-empty-state :title="__('files.empty')" />
        @endif
    </x-card>
</x-layouts.app>
