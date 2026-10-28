<x-layouts.app :title="__('files.confirm_delete')">
    <x-page-header :title="__('files.confirm_delete')"><x-slot:actions><x-button :href="route('files.index')" variant="outline-secondary">{{ __('files.back') }}</x-button></x-slot:actions></x-page-header>
    <div class="d-grid gap-3"><div><x-card>
        <div class="d-flex align-items-center gap-3 mb-4"><x-file-icon :extension="$file->extension" /><strong class="text-break">{{ $file->original_name }}</strong></div>
        <p>{{ __('files.delete_explanation') }}</p>
        <x-form :action="route('files.destroy', $file->public_id)" method="DELETE">
            <div class="d-grid gap-2"><x-button type="submit" variant="danger">{{ __('files.delete') }}</x-button><x-button :href="route('files.index')" variant="outline-secondary">{{ __('ui.cancel') }}</x-button></div>
        </x-form>
    </x-card></div></div>
</x-layouts.app>
