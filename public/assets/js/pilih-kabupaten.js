/**
 * Mengisi <select data-kabupaten> dengan seluruh kab/kota, dikelompokkan per provinsi.
 * Nilai terpilih diambil dari atribut data-pilih.
 */
(async () => {
    'use strict';
    const daftar = document.querySelectorAll('select[data-kabupaten], #kabupaten_kode[data-pilih]');
    if (daftar.length === 0) return;

    const api = (document.getElementById('wilayah')?.dataset.api || document.querySelector('[data-api]')?.dataset.api) + '?tingkat=2';
    const rows = await (await fetch(api)).json();

    daftar.forEach((sel) => {
        let grup = null;
        rows.forEach((r) => {
            if (!grup || grup.label !== r.provinsi) {
                grup = document.createElement('optgroup');
                grup.label = r.provinsi;
                sel.append(grup);
            }
            const banyak = (sel.dataset.pilihBanyak || '').split(',');
            grup.append(new Option(r.nama, r.kode, false, r.kode === sel.dataset.pilih || banyak.includes(r.kode)));
        });
    });
})();
