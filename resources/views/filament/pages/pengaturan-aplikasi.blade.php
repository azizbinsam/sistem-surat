<x-filament-panels::page>
    <form wire:submit="simpan" class="space-y-4">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                Simpan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
