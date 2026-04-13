<?php

namespace App\Http\Controllers;

use App\Models\DeletionHistory;
use App\Models\ItemPembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanPotongan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class TagihanController extends Controller
{
    public function create()
    {
        $siswas = Siswa::where('status', 'aktif')
            ->orderBy('nama')
            ->get(['id', 'nis', 'nama', 'kategori', 'status', 'kelas']);

        $items = ItemPembayaran::where('aktif', true)->orderBy('id')->get();

        return view('keuangan.tagihan-create', compact('siswas', 'items'));
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', $request->get('nis', '')));
        $jenjang = strtoupper(trim((string) $request->get('jenjang', '')));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $siswaRows = Siswa::query()
            ->with(['tagihans' => function ($query) {
                $query->with([
                    'itemPembayaran',
                    'potongans' => function ($potonganQuery) {
                        $potonganQuery->orderByDesc('tanggal_potongan')->orderByDesc('id');
                    },
                ])
                    ->withSum('potongans as total_potongan', 'nominal_potongan')
                    ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
                    ->whereIn('status', ['belum_lunas', 'sebagian'])
                    ->orderByDesc('id');
            }])
            ->whereHas('tagihans', function ($query) {
                $query->whereIn('status', ['belum_lunas', 'sebagian']);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('nis', 'like', '%' . $q . '%')
                        ->orWhere('nama', 'like', '%' . $q . '%');
                });
            });

        $this->applyJenjangFilter($siswaRows, $jenjang);

        $siswaRows = $siswaRows
            ->orderBy('nama')
            ->paginate($perPage)
            ->appends($request->query());

        $siswas = Siswa::where('status', 'aktif')->orderBy('nama')->get(['id', 'nis', 'nama', 'kategori', 'status']);
        $items = ItemPembayaran::where('aktif', true)->orderBy('id')->get();
        $siswaSearch = $siswas->map(function ($siswa) {
            return [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'kategori' => $siswa->kategori,
            ];
        })->values();

        return view('keuangan.tagihan', compact('siswaRows', 'siswas', 'siswaSearch', 'items', 'q', 'jenjang', 'perPage'));
    }

    private function applyJenjangFilter($query, string $jenjang): void
    {
        $jenjang = match (strtoupper(trim($jenjang))) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            '13' => 'XIII',
            default => strtoupper(trim($jenjang)),
        };

        if (!in_array($jenjang, ['X', 'XI', 'XII', 'XIII'], true)) {
            return;
        }

        $query->where(function ($kelasQuery) use ($jenjang) {
            if ($jenjang === 'X') {
                $kelasQuery->where('kelas', 'like', '10%')->orWhere('kelas', '=', 'X')->orWhere('kelas', 'like', 'X %');
            }
            if ($jenjang === 'XI') {
                $kelasQuery->where('kelas', 'like', '11%')->orWhere('kelas', '=', 'XI')->orWhere('kelas', 'like', 'XI %');
            }
            if ($jenjang === 'XII') {
                $kelasQuery->where('kelas', 'like', '12%')->orWhere('kelas', '=', 'XII')->orWhere('kelas', 'like', 'XII %');
            }
            if ($jenjang === 'XIII') {
                $kelasQuery->where('kelas', 'like', '13%')->orWhere('kelas', '=', 'XIII')->orWhere('kelas', 'like', 'XIII %');
            }
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'item_pembayaran_ids' => 'required|array|min:1',
            'item_pembayaran_ids.*' => 'integer|exists:item_pembayarans,id',
            'periode_bulan' => 'required|array|min:1',
            'periode_bulan.*' => 'integer|min:1|max:12',
            'periode_tahun' => 'nullable|integer|min:2000|max:2100',
            'nominal_awal' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $items = ItemPembayaran::query()
            ->whereIn('id', $validated['item_pembayaran_ids'])
            ->get()
            ->keyBy('id');

        if (empty($siswa->nis) || empty($siswa->nama)) {
            return back()->with('error', 'Data siswa tidak valid: NIS atau nama siswa tidak tersedia.')->withInput();
        }

        if ($siswa->status === 'lulus') {
            return back()->with('error', 'Siswa ' . $siswa->nama . ' sudah lulus, tagihan baru tidak dapat dibuat.')->withInput();
        }

        $itemTidakSesuai = $items->filter(function (ItemPembayaran $item) use ($siswa) {
            return ($item->berlaku_untuk === 'mondok' && $siswa->kategori !== 'mondok')
                || ($item->berlaku_untuk === 'non_mondok' && $siswa->kategori !== 'non_mondok');
        });

        if ($itemTidakSesuai->isNotEmpty()) {
            $labels = $itemTidakSesuai->pluck('nama_item')->implode(', ');
            return back()->with('error', 'Item tidak sesuai kategori siswa: ' . $labels . '.')->withInput();
        }

        $bulanList = collect($validated['periode_bulan'])
            ->map(fn ($bulan) => (int) $bulan)
            ->unique()
            ->values();

        $itemIds = collect($validated['item_pembayaran_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $nominalAwal = (float) $validated['nominal_awal'];

        $created = 0;
        $skipped = 0;

        foreach ($itemIds as $itemId) {
            $item = $items->get($itemId);
            if (!$item) {
                continue;
            }

            $nominalDefaultItem = (float) ($item->nominal ?? 0);

            foreach ($bulanList as $bulan) {
                $exists = Tagihan::query()
                    ->where('siswa_id', $siswa->id)
                    ->where('item_pembayaran_id', $item->id)
                    ->where('periode_bulan', $bulan)
                    ->where('periode_tahun', $validated['periode_tahun'] ?? null)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $payload = [
                    'siswa_id' => $siswa->id,
                    'item_pembayaran_id' => $item->id,
                    'periode_bulan' => $bulan,
                    'periode_tahun' => $validated['periode_tahun'] ?? null,
                    'nominal_awal' => $nominalAwal > 0 ? $nominalAwal : $nominalDefaultItem,
                    'status' => 'belum_lunas',
                    'catatan' => $validated['catatan'] ?? null,
                ];

                if (Schema::hasColumn('tagihans', 'kelas')) {
                    $payload['kelas'] = $siswa->kelas;
                }

                Tagihan::create($payload);
                $created++;
            }
        }

        $message = $created > 0
            ? 'Tagihan berhasil dibuat.'
            : 'Tidak ada tagihan baru yang dibuat.';

        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' bulan dilewati karena sudah ada tagihan.';
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Tagihan $tagihan)
    {
        $validated = $request->validate([
            'periode_bulan' => 'nullable|integer|min:1|max:12',
            'periode_tahun' => 'nullable|integer|min:2000|max:2100',
            'nominal_awal' => 'required|numeric|min:1',
            'catatan' => 'nullable|string',
        ]);

        $tagihan->update([
            'periode_bulan' => $validated['periode_bulan'] ?? null,
            'periode_tahun' => $validated['periode_tahun'] ?? null,
            'nominal_awal' => $validated['nominal_awal'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        $tagihan->fresh()->sinkronkanStatus();

        return back()->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function tambahPotongan(Request $request, Tagihan $tagihan)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_potongan' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal_potongan' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_potongan_modal_tagihan_id', $tagihan->id);
        }

        $validated = $validator->validated();

        $sisaSaatIni = $tagihan->sisaTagihan();
        if ($sisaSaatIni <= 0) {
            return back()->with('error', 'Tagihan sudah lunas, potongan tidak dapat ditambahkan.');
        }

        if ((float) $validated['nominal_potongan'] > $sisaSaatIni) {
            return back()->with('error', 'Nominal potongan melebihi sisa tagihan.');
        }

        TagihanPotongan::create([
            'tagihan_id' => $tagihan->id,
            'tanggal_potongan' => $validated['tanggal_potongan'],
            'keterangan' => $validated['keterangan'],
            'nominal_potongan' => $validated['nominal_potongan'],
        ]);

        $tagihan->fresh()->sinkronkanStatus();

        return back()->with('success', 'Potongan tagihan berhasil ditambahkan.');
    }

    public function updatePotongan(Request $request, Tagihan $tagihan, TagihanPotongan $potongan)
    {
        if ((int) $potongan->tagihan_id !== (int) $tagihan->id) {
            return back()->with('error', 'Data potongan tidak sesuai dengan tagihan.');
        }

        $validator = Validator::make($request->all(), [
            'tanggal_potongan' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal_potongan' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_potongan_edit_modal_id', $potongan->id);
        }

        $validated = $validator->validated();

        $batasPotongan = $tagihan->sisaTagihan() + (float) $potongan->nominal_potongan;
        if ((float) $validated['nominal_potongan'] > $batasPotongan) {
            return back()->with('error', 'Nominal potongan melebihi sisa tagihan yang tersedia.');
        }

        $potongan->update([
            'tanggal_potongan' => $validated['tanggal_potongan'],
            'keterangan' => $validated['keterangan'],
            'nominal_potongan' => $validated['nominal_potongan'],
        ]);

        $tagihan->fresh()->sinkronkanStatus();

        return back()->with('success', 'Potongan tagihan berhasil diperbarui.');
    }

    public function hapusPotongan(Tagihan $tagihan, TagihanPotongan $potongan)
    {
        if ((int) $potongan->tagihan_id !== (int) $tagihan->id) {
            return back()->with('error', 'Data potongan tidak sesuai dengan tagihan.');
        }

        $potongan->delete();
        $tagihan->fresh()->sinkronkanStatus();

        return back()->with('success', 'Potongan tagihan berhasil dihapus.');
    }

    public function destroy(Tagihan $tagihan)
    {
        $siswaNama = $tagihan->siswa->nama ?? 'Siswa';
        $itemNama = $tagihan->itemPembayaran->nama_item ?? 'Item';

        DeletionHistory::create([
            'menu' => 'Tagihan',
            'entity_type' => 'Tagihan',
            'entity_id' => $tagihan->id,
            'label' => $itemNama . ' - ' . $siswaNama,
            'deleted_by' => auth()->id(),
            'deleted_at' => now(),
        ]);

        $tagihan->delete();

        return back()->with('success', 'Tagihan ' . $itemNama . ' untuk ' . $siswaNama . ' berhasil dihapus.');
    }
}
