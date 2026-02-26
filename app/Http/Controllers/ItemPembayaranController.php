<?php

namespace App\Http\Controllers;

use App\Models\ItemPembayaran;
use Illuminate\Http\Request;

class ItemPembayaranController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $items = ItemPembayaran::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('kode', 'like', '%' . $search . '%')
                    ->orWhere('nama_item', 'like', '%' . $search . '%');
            })
            ->orderBy('nama_item')
            ->paginate(10)
            ->appends($request->query());

        return view('master.item-pembayaran', compact('items', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:30|unique:item_pembayarans,kode',
            'nama_item' => 'required|string|max:255',
            'jenis_item' => 'required|in:tetap,fleksibel',
            'berlaku_untuk' => 'required|in:mondok,non_mondok,semua',
            'pengelola' => 'required|in:yayasan,sekolah',
            'aktif' => 'nullable|boolean',
        ]);

        $validated['aktif'] = (bool) ($validated['aktif'] ?? false);

        ItemPembayaran::create($validated);

        return back()->with('success', 'Item pembayaran berhasil ditambahkan.');
    }
}
