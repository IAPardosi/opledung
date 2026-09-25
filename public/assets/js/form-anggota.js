/**
 * Form anggota: pilihan wilayah bertingkat (provinsi → desa) dan
 * isian wafat yang hanya tampil bila status "Meninggal".
 */
(function () {
    'use strict';

    const wadah = document.getElementById('wilayah');
    if (wadah) {
        const tingkat = ['provinsi', 'kabupaten', 'kecamatan', 'desa'];
        const kosong = ['– Pilih provinsi –', '– Pilih kabupaten/kota –', '– Pilih kecamatan –', '– Pilih desa/kelurahan –'];
        const select = tingkat.map((t) => document.getElementById(t + '_kode'));

        async function isi(i, induk, terpilih) {
            const el = select[i];
            el.innerHTML = `<option value="">${kosong[i]}</option>`;
            for (let j = i + 1; j < select.length; j++) {
                select[j].innerHTML = `<option value="">${kosong[j]}</option>`;
                select[j].disabled = true;
            }
            if (i > 0 && !induk) {
                el.disabled = true;
                return;
            }
            const url = wadah.dataset.api + (induk ? '?induk=' + encodeURIComponent(induk) : '');
            const rows = await (await fetch(url)).json();
            rows.forEach((r) => el.add(new Option(r.nama, r.kode, false, r.kode === terpilih)));
            el.disabled = false;
        }

        select.forEach((el, i) => {
            if (i < select.length - 1) {
                el.addEventListener('change', () => isi(i + 1, el.value, ''));
            }
        });

        (async () => {
            await isi(0, null, wadah.dataset.provinsi);
            for (let i = 1; i < tingkat.length; i++) {
                const induk = wadah.dataset[tingkat[i - 1]];
                if (!induk) break;
                await isi(i, induk, wadah.dataset[tingkat[i]]);
            }
        })();
    }

    const status = document.getElementById('status_hidup');
    if (status) {
        const atur = () => document.querySelectorAll('.wafat').forEach((el) => {
            el.style.display = status.value === 'meninggal' ? '' : 'none';
        });
        status.addEventListener('change', atur);
        atur();
    }
})();
