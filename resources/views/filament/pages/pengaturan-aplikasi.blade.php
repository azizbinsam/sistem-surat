<x-filament-panels::page>
    <form wire:submit="simpan">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Simpan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
