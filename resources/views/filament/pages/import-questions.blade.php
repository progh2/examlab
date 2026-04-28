<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::button wire:click="import">
            Import
        </x-filament::button>

        @if (count($errors) > 0)
            <div class="p-4 rounded bg-danger-50 text-danger-700">
                <div class="font-semibold mb-2">Errors</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (count($previewRows) > 0)
            <div class="p-4 rounded bg-gray-50">
                <div class="font-semibold mb-2">Preview (first 5 inserted rows)</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($previewRows as $row)
                        <li>
                            L{{ $row['line'] }} /
                            {{ $row['section_key'] }} /
                            {{ $row['topic_key'] }} /
                            {{ $row['correct_choice_index'] }} /
                            {{ $row['stem_ko'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-filament-panels::page>
