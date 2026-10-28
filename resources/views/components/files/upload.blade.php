<x-card :title="__('files.upload')">
    <x-form :action="route('files.store')" enctype="multipart/form-data">
        <x-form-input name="file" type="file" :label="__('files.choose_file')" :hint="__('files.upload_hint', ['limit' => \App\Support\ByteFormatter::format(config('files.max_upload_bytes'))])" :data-max-bytes="config('files.max_upload_bytes')" :data-size-error="__('files.too_large')" required />
        <x-button type="submit">{{ __('files.upload') }}</x-button>
    </x-form>
</x-card>
