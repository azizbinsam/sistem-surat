    <?php
    
    use App\Models\Pegawai;
    use Livewire\Attributes\Layout;
    use Livewire\Volt\Component;
    use Livewire\WithFileUploads;
    
    new #[Layout('layouts.app')] class extends Component {
        use WithFileUploads;
    
        public string $nama = '';
        public string $nip = '';
        public string $jabatan = '';
        public string $kategori = '';
        public $ttd;
    
        public array $opsiKategori = [
            'kepala_sekolah' => 'Kepala Sekolah',
            'pengurus_barang_pembantu' => 'Pengurus Barang Pembantu',
            'guru' => 'Guru',
            'tendik' => 'Tendik',
        ];
    
        public function simpan(): void
        {
            $validated = $this->validate([
                'nama' => ['required', 'string', 'max:255'],
                'nip' => ['nullable', 'string', 'max:50'],
                'jabatan' => ['required', 'string', 'max:255'],
                'kategori' => ['required', 'in:kepala_sekolah,pengurus_barang_pembantu,guru,tendik'],
                'ttd' => ['nullable', 'image', 'max:2048'], // maks 2MB
            ]);
    
            $ttdPath = null;
            if ($this->ttd) {
                $ttdPath = $this->ttd->store('ttd', 'public');
            }
    
            Pegawai::create([...$validated, 'sekolah_id' => auth()->user()->sekolah_id, 'ttd_path' => $ttdPath]);
    
            session()->flash('success', 'Pegawai berhasil ditambahkan.');
            $this->redirect(route('pegawai.index'), navigate: true);
        }
    }; ?>

    <div class="max-w-xl">
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">Tambah Pegawai</h2>

        <form wire:submit="simpan" class="space-y-4 bg-white p-6 rounded-md shadow">
            <div>
                <x-input-label for="nama" value="Nama Lengkap" />
                <x-text-input wire:model="nama" id="nama" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="nip" value="NIP (opsional)" />
                <x-text-input wire:model="nip" id="nip" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('nip')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="jabatan" value="Jabatan" />
                <x-text-input wire:model="jabatan" id="jabatan" class="block mt-1 w-full" type="text"
                    placeholder="Operator Layanan Operasional, Guru Kelas 3, dst" />
                <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="kategori" value="Kategori" />
                <select wire:model="kategori" id="kategori"
                    class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($opsiKategori as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('kategori')" class="mt-2" />
                <p class="text-xs text-gray-500 mt-1">Kategori ini menentukan otomatis muncul di penandatangan surat
                    mana (NPB/SPB/SPPB).</p>
            </div>

            <div>
                <x-input-label for="ttd" value="Tanda Tangan (opsional, bisa diisi belakangan)" />
                <input type="file" wire:model="ttd" id="ttd" accept="image/*"
                    class="block mt-1 w-full text-sm">
                <x-input-error :messages="$errors->get('ttd')" class="mt-2" />
                <div wire:loading wire:target="ttd" class="text-xs text-zinc-500 mt-1">Mengunggah gambar...</div>
                @if ($ttd)
                    <img src="{{ $ttd->temporaryUrl() }}" class="mt-2 h-16 border rounded">
                @endif
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ route('pegawai.index') }}" wire:navigate
                    class="text-sm text-zinc-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
