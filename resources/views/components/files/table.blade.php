@props(['files', 'global' => false])
<div class="table-responsive" role="region" aria-label="{{ __('files.all_files') }}" tabindex="0">
    <table class="table align-middle account-table file-table mb-0">
        <thead><tr><th scope="col">{{ __('files.name') }}</th><th scope="col">{{ __('files.size') }}</th><th scope="col">{{ __('files.status') }}</th>@if($global)<th scope="col">{{ __('files.added_by') }}</th>@endif<th scope="col">{{ __('files.added') }}</th><th scope="col" class="text-end">{{ __('files.actions') }}</th></tr></thead>
        <tbody>
            @foreach($files as $file)
                @php($displayStatus = \App\Support\FilePresentation::displayStatus($file))
                <tr>
                    <th scope="row"><div class="d-flex align-items-center gap-2"><x-file-icon :extension="$file->extension" /><span class="file-name fw-medium">{{ $file->original_name }}</span></div></th>
                    <td class="text-nowrap">{{ \App\Support\ByteFormatter::format($file->size_bytes) }}</td>
                    <td>
                        <x-file-status :status="$displayStatus" />
                        @if($file->failure_code)<p class="small text-body-secondary mt-1 mb-0">{{ \App\Support\FilePresentation::failureMessage($file->failure_code) }}</p>@endif
                    </td>
                    @if($global)<td><span class="d-block text-break">{{ $file->owner->display_name }}</span><span class="d-block small text-body-secondary text-break">{{ $file->organisation->name }}</span></td>@endif
                    <td class="text-nowrap"><time datetime="{{ $file->created_at->toIso8601String() }}">{{ $file->created_at->format('d M Y, H:i') }}</time></td>
                    <td><div class="d-flex justify-content-end gap-2">
                        @can('download', $file)<x-button :href="route('files.download', $file->public_id)" variant="outline-primary" :aria-label="__('files.download').': '.$file->original_name">{{ __('files.download') }}</x-button>@endcan
                        @if($displayStatus !== \App\Enums\FileStatus::Deleting)
                            @can('delete', $file)<x-button :href="route('files.delete', $file->public_id)" variant="outline-danger" :aria-label="__('files.delete').': '.$file->original_name">{{ __('files.delete') }}</x-button>@endcan
                        @endif
                    </div></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
