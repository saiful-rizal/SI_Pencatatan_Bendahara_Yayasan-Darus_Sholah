<?php

namespace App\Http\Controllers;

use App\Models\DeletionHistory;
use App\Models\ItemPembayaran;
use Illuminate\Http\Request;

class ItemPembayaranController extends Controller
{
    public function create()
    {
        return view('master.item-pembayaran-create', [
            'nextKodeItem' => $this->generateKodeItem(),
        ]);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $items = ItemPembayaran::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('kode', 'like', '%' . $search . '%')
                    ->orWhere('nama_item', 'like', '%' . $search . '%');
            })
            ->orderBy('id')
            ->paginate(10)
            ->appends($request->query());

        return view('master.item-pembayaran', [
            'items' => $items,
            'search' => $search,
            'nextKodeItem' => $this->generateKodeItem(),
        ]);
    }

    public function store(Request $request)
    {
        $kode = trim((string) $request->input('kode', ''));

        if ($kode === '') {
            $kode = $this->generateKodeItem();
        }

        $validated = $request->validate([
            'kode' => 'nullable|string|max:30|unique:item_pembayarans,kode',
            'nama_item' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
            'jenis_item' => 'required|in:tetap,fleksibel',
            'berlaku_untuk' => 'required|in:mondok,non_mondok,semua',
            'pengelola' => 'required|in:yayasan,sekolah',
            'aktif' => 'nullable|boolean',
        ]);

        $validated['kode'] = $kode;
        $validated['aktif'] = (bool) ($validated['aktif'] ?? false);

        ItemPembayaran::create($validated);

        return redirect()->route('item.index')->with('success', 'Item pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, ItemPembayaran $item)
    {
        $validated = $request->validate([
            'nama_item' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
            'jenis_item' => 'required|in:tetap,fleksibel',
            'berlaku_untuk' => 'required|in:mondok,non_mondok,semua',
            'pengelola' => 'required|in:yayasan,sekolah',
            'aktif' => 'nullable|boolean',
        ]);

        $validated['aktif'] = (bool) ($validated['aktif'] ?? false);

        $item->update($validated);

        return back()->with('success', 'Item pembayaran ' . $item->nama_item . ' berhasil diperbarui.');
    }

    public function toggleAktif(ItemPembayaran $item)
    {
        $item->update([
            'aktif' => !$item->aktif,
        ]);

        $status = $item->aktif ? 'aktif' : 'nonaktif';
        return back()->with('success', 'Status item pembayaran ' . $item->nama_item . ' diubah menjadi ' . $status . '.');
    }

    public function destroy(ItemPembayaran $item)
    {
        $namaItem = $item->nama_item;

        DeletionHistory::create([
            'menu' => 'Item Pembayaran',
            'entity_type' => 'ItemPembayaran',
            'entity_id' => $item->id,
            'label' => $item->kode . ' - ' . $namaItem,
            'deleted_by' => auth()->id(),
            'deleted_at' => now(),
        ]);

        $item->delete();

        return back()->with('success', 'Item pembayaran ' . $namaItem . ' berhasil dihapus.');
    }

    private function generateKodeItem(): string
    {
        $lastKode = (string) ItemPembayaran::query()
            ->select('kode')
            ->orderByDesc('id')
            ->value('kode');

        if (preg_match('/^(?:ITM|ITEM)-?(\d+)$/i', $lastKode, $matches) === 1) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = ((int) ItemPembayaran::count()) + 1;
        }

        return 'ITM-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
