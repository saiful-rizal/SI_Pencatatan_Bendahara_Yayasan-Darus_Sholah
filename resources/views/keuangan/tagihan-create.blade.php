@extends('layouts.app')

@section('content')
@php
    $bulanOptions = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Tambah Tagihan</h4>
        <small class="text-muted">Form terpisah untuk input tagihan baru per siswa.</small>
    </div>
    <a href="{{ route('tagihan.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            Pilih siswa dan item sesuai kategori siswa: <strong>Mondok</strong>, <strong>Non Mondok</strong>, <strong>Alumni</strong>, atau <strong>Non Alumni</strong>. Item dengan kategori <strong>Semua</strong> akan selalu muncul untuk siswa kategori apapun.
        </div>
        <form action="{{ route('tagihan.store') }}" method="POST" class="row g-2" id="formTagihanCreate">
            @csrf
            <div class="col-md-4">
                <label class="form-label small">Siswa (NIS - Nama - Kategori)</label>
                <select name="siswa_id" class="form-select" id="siswaSelect" required>
                    <option value="">Pilih Siswa</option>
                    @foreach($siswas as $siswa)
                        @php
                            $labelKategori = ['mondok' => 'Mondok', 'non_mondok' => 'Non Mondok', 'alumni' => 'Alumni', 'non_alumni' => 'Non Alumni'][$siswa->kategori] ?? ucfirst(str_replace('_', ' ', $siswa->kategori));
                        @endphp
                        <option value="{{ $siswa->id }}" data-kategori="{{ $siswa->kategori }}">{{ $siswa->nis }} - {{ $siswa->nama }} ({{ $labelKategori }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Item Pembayaran</label>
                <div class="dropdown w-100" data-bs-auto-close="outside">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start py-2 px-3" type="button" id="itemDropdownButton" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="min-height: 48px; white-space: normal;">
                        Pilih Item
                    </button>
                    <div class="dropdown-menu p-2 shadow-sm border-0" aria-labelledby="itemDropdownButton" id="itemDropdownMenu" style="max-height: 420px; overflow-y: auto; width: max(100%, 620px);">
                        <div class="dropdown-item rounded px-2 py-2 mb-1 d-flex align-items-center gap-2 border-bottom">
                            <input class="form-check-input mt-0" type="checkbox" id="checkAllItem">
                            <label class="small fw-semibold w-100 mb-0" for="checkAllItem">Pilih Semua Item</label>
                        </div>
                        @foreach($items as $item)
                            <div class="dropdown-item rounded px-2 py-2 mb-1 item-option d-flex align-items-center gap-2" data-berlaku="{{ $item->berlaku_untuk }}">
                                <input class="form-check-input item-checkbox mt-0" type="checkbox" name="item_pembayaran_ids[]" value="{{ $item->id }}" data-label="{{ $item->kode }} - {{ $item->nama_item }} - Rp {{ number_format((float) ($item->nominal ?? 0), 0, ',', '.') }} ({{ $item->berlaku_untuk }})" data-nominal="{{ (float) ($item->nominal ?? 0) }}" data-berlaku="{{ $item->berlaku_untuk }}" id="itemCreate{{ $item->id }}" {{ in_array((string) $item->id, array_map('strval', old('item_pembayaran_ids', [])), true) ? 'checked' : '' }}>
                                <label class="small w-100 lh-sm mb-0" for="itemCreate{{ $item->id }}">
                                    <span class="d-block fw-semibold">{{ $item->kode }} - {{ $item->nama_item }}</span>
                                    <span class="d-block text-muted">Rp {{ number_format((float) ($item->nominal ?? 0), 0, ',', '.') }} · {{ $item->berlaku_untuk }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small d-block">Bulan (centang bisa lebih dari satu)</label>
                <div class="dropdown w-100" data-bs-auto-close="outside">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start py-2 px-3" type="button" id="bulanDropdownButtonCreate" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="min-height: 44px; white-space: normal;">
                        Pilih Bulan
                    </button>
                    <div class="dropdown-menu p-3 shadow-sm border-0" id="bulanDropdownMenuCreate" style="max-height: 300px; overflow-y: auto; width: max(100%, 420px);">
                        <div class="dropdown-item rounded px-3 py-2 mb-2 d-flex align-items-center gap-2 border-bottom">
                            <input class="form-check-input mt-0" type="checkbox" id="checkAllBulan">
                            <label class="small fw-semibold w-100 mb-0" for="checkAllBulan">Pilih Semua Bulan</label>
                        </div>
                        @foreach($bulanOptions as $bulanAngka => $bulanNama)
                            <div class="dropdown-item rounded px-3 py-2 mb-2 bulan-option-create d-flex align-items-center gap-2">
                                <input class="form-check-input bulan-checkbox-create mt-0" type="checkbox" name="periode_bulan[]" value="{{ $bulanAngka }}" id="bulanCreate{{ $bulanAngka }}" {{ in_array((string) $bulanAngka, array_map('strval', old('periode_bulan', [])), true) ? 'checked' : '' }}>
                                <label class="small w-100 mb-0" for="bulanCreate{{ $bulanAngka }}">{{ $bulanNama }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-2"><label class="form-label small">Tahun</label><input type="number" name="periode_tahun" min="2000" max="2100" class="form-control"></div>
            <div class="col-md-6"><label class="form-label small">Catatan</label><input name="catatan" class="form-control"></div>
            <div class="col-12 mt-2">
                <div id="totalRingkasanCreate" class="bg-light rounded p-3 border small d-none">
                    <div class="fw-semibold mb-1">Ringkasan Tagihan</div>
                    <div id="daftarItemCreate"></div>
                    <hr class="my-1">
                    <div class="d-flex justify-content-between">
                        <span>Total Tagihan</span>
                        <span id="totalNominalCreate" class="fw-bold text-primary">Rp 0</span>
                    </div>
                </div>
            </div>
            <div class="col-md-12 d-grid mt-2"><button class="btn btn-dark">Simpan Tagihan</button></div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const siswaSelect = document.getElementById('siswaSelect');
        const itemDropdownButton = document.getElementById('itemDropdownButton');
        const itemCheckboxes = Array.from(document.querySelectorAll('.item-checkbox'));
        const itemOptions = Array.from(document.querySelectorAll('.item-option'));
        const bulanDropdownButton = document.getElementById('bulanDropdownButtonCreate');
        const bulanCheckboxes = Array.from(document.querySelectorAll('.bulan-checkbox-create'));
        const itemDropdownMenu = document.getElementById('itemDropdownMenu');
        const bulanDropdownMenu = document.getElementById('bulanDropdownMenuCreate');

        [itemDropdownMenu, bulanDropdownMenu].forEach((menu) => {
            if (!menu) {
                return;
            }

            menu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });

        function filterItemsBySiswaKategori() {
            const selected = siswaSelect.options[siswaSelect.selectedIndex];
            const kategori = selected ? selected.getAttribute('data-kategori') : null;

            itemOptions.forEach((option) => {
                const berlaku = option.getAttribute('data-berlaku');
                const visible = !kategori || berlaku === 'semua' || berlaku === kategori;
                option.classList.toggle('d-none', !visible);
            });

            const checkedVisible = itemCheckboxes.find((checkbox) => checkbox.checked && !checkbox.closest('.item-option')?.classList.contains('d-none'));
            if (!checkedVisible) {
                setSelectedItemLabel('Pilih Item');
            }

            itemCheckboxes.forEach((checkbox) => {
                if (checkbox.checked && checkbox.closest('.item-option')?.classList.contains('d-none')) {
                    checkbox.checked = false;
                }
            });

            var checkAll = document.getElementById('checkAllItem');
            if (checkAll) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }

            updateSelectedItemsState();
        }

        function hitungTotalCreate() {
            const nominalMap = {};
            itemCheckboxes.forEach(function (cb) {
                if (cb.checked) {
                    const namaEl = cb.parentElement?.querySelector('.fw-semibold');
                    const label = namaEl ? namaEl.textContent.trim() : (cb.getAttribute('data-label') || 'Item');
                    const nominal = parseFloat(cb.getAttribute('data-nominal')) || 0;
                    nominalMap[cb.value] = { label: label, nominal: nominal };
                }
            });

            const bulanCount = bulanCheckboxes.filter(function (cb) { return cb.checked; }).length;
            const elRingkasan = document.getElementById('totalRingkasanCreate');
            const elDaftar = document.getElementById('daftarItemCreate');
            const elTotal = document.getElementById('totalNominalCreate');

            if (Object.keys(nominalMap).length === 0 || bulanCount === 0) {
                elRingkasan.classList.add('d-none');
                return;
            }

            let total = 0;
            let html = '';
            for (const key in nominalMap) {
                const item = nominalMap[key];
                const subtotal = item.nominal * bulanCount;
                total += subtotal;
                html += '<div class="d-flex justify-content-between"><span>' + item.label + '</span><span>Rp ' + Number(subtotal).toLocaleString('id-ID') + '</span></div>';
            }
            elDaftar.innerHTML = html;
            elTotal.textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
            elRingkasan.classList.remove('d-none');
        }

        function setSelectedItemLabel(label) {
            if (itemDropdownButton) {
                itemDropdownButton.textContent = label;
            }
        }

        function updateSelectedItemsState() {
            const selected = itemCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.getAttribute('data-label') || '')
                .filter(Boolean);

            if (selected.length === 0) {
                setSelectedItemLabel('Pilih Item');
            } else if (selected.length <= 2) {
                setSelectedItemLabel(selected.join(', '));
            } else {
                setSelectedItemLabel(`${selected.length} item dipilih`);
            }

            hitungTotalCreate();
        }

        itemCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                updateSelectedItemsState();
            });
        });

        itemOptions.forEach((option) => {
            option.addEventListener('click', function (event) {
                if (event.target instanceof HTMLInputElement) {
                    return;
                }

                event.preventDefault();

                const checkbox = option.querySelector('.item-checkbox');
                if (!checkbox || checkbox.disabled) {
                    return;
                }

                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        function updateBulanButtonLabel() {
            if (!bulanDropdownButton) {
                return;
            }

            const selected = bulanCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.nextElementSibling ? checkbox.nextElementSibling.textContent.trim() : '')
                .filter(Boolean);

            if (selected.length === 0) {
                bulanDropdownButton.textContent = 'Pilih Bulan';
                return;
            }

            if (selected.length <= 2) {
                bulanDropdownButton.textContent = selected.join(', ');
                return;
            }

            bulanDropdownButton.textContent = `${selected.length} bulan dipilih`;
        }

        bulanCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                updateBulanButtonLabel();
                hitungTotalCreate();
            });
        });

        document.querySelectorAll('.bulan-option-create').forEach((option) => {
            option.addEventListener('click', function (event) {
                if (event.target instanceof HTMLInputElement || event.target instanceof HTMLLabelElement) {
                    return;
                }

                const checkbox = option.querySelector('.bulan-checkbox-create');
                if (!checkbox || checkbox.disabled) {
                    return;
                }

                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        document.getElementById('checkAllItem').addEventListener('change', function() {
            var checked = this.checked;
            document.querySelectorAll('.item-checkbox:not(:disabled)').forEach(function(cb) {
                var option = cb.closest('.item-option');
                if (option && !option.classList.contains('d-none')) {
                    cb.checked = checked;
                }
            });
            updateSelectedItemsState();
        });

        document.querySelectorAll('.item-checkbox').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var allVisible = document.querySelectorAll('.item-option:not(.d-none) .item-checkbox');
                var allChecked = document.querySelectorAll('.item-option:not(.d-none) .item-checkbox:checked');
                var checkAll = document.getElementById('checkAllItem');
                if (checkAll) {
                    checkAll.checked = allVisible.length > 0 && allVisible.length === allChecked.length;
                    checkAll.indeterminate = allChecked.length > 0 && allChecked.length < allVisible.length;
                }
            });
        });

        document.getElementById('checkAllBulan').addEventListener('change', function() {
            var checked = this.checked;
            document.querySelectorAll('.bulan-checkbox-create').forEach(function(cb) {
                cb.checked = checked;
            });
            updateBulanButtonLabel();
            hitungTotalCreate();
        });

        document.querySelectorAll('.bulan-checkbox-create').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var all = document.querySelectorAll('.bulan-checkbox-create');
                var allChecked = document.querySelectorAll('.bulan-checkbox-create:checked');
                var checkAll = document.getElementById('checkAllBulan');
                if (checkAll) {
                    checkAll.checked = all.length > 0 && all.length === allChecked.length;
                    checkAll.indeterminate = allChecked.length > 0 && allChecked.length < all.length;
                }
            });
        });

        filterItemsBySiswaKategori();
        updateSelectedItemsState();
        updateBulanButtonLabel();

        siswaSelect.addEventListener('change', filterItemsBySiswaKategori);
    });
</script>

<style>
    .item-option {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease;
    }

    .item-option:hover {
        background-color: #eef4ff;
    }

    .item-option:active {
        background-color: #dde9ff;
    }
</style>
@endsection
