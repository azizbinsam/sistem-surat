@props(['options' => [], 'model', 'placeholder' => 'Cari...'])

<div x-data="{
    open: false,
    search: '',
    options: {{ Illuminate\Support\Js::from($options) }},
    get filtered() {
        if (this.search === '') return this.options;
        return this.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()));
    },
    select(option) {
        $wire.set('{{ $model }}', option.id);
        this.search = option.label;
        this.open = false;
    },
    init() {
        let current = $wire.get('{{ $model }}');
        let found = this.options.find(o => o.id == current);
        this.search = found ? found.label : '';
    }
}" x-init="init()" @click.outside="open = false" class="relative">
    <input type="text" x-model="search" @focus="open = true" placeholder="{{ $placeholder }}"
        class="border-gray-300 focus:border-zinc-500 focus:ring-zinc-500 rounded-md shadow-sm block mt-1 w-full"
        autocomplete="off">
    <div x-show="open" x-transition
        class="absolute z-20 mt-1 w-full bg-white border border-zinc-200 rounded-lg shadow-lg max-h-56 overflow-y-auto"
        style="display: none;">
        <template x-for="option in filtered" :key="option.id">
            <div @click="select(option)" class="px-3 py-2 text-sm hover:bg-emerald-50 cursor-pointer"
                x-text="option.label"></div>
        </template>
        <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-400">Tidak ditemukan</div>
    </div>
</div>
