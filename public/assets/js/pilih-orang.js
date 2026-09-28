/**
 * Kotak pencarian anggota: <input class="pilih-orang" data-target="idHidden"> diikuti .hasil-pilih.
 * Memakai SaranOrang (kartu berfoto, huta, "anak ni …"). Opsi data-garis="utama,boru" membatasi hasil.
 * Saat dipilih, input tersembunyi terisi id serta data-generasi dan data-garis, lalu memicu 'change'.
 */
(function () {
    'use strict';

    document.querySelectorAll('input.pilih-orang').forEach((input) => {
        const hidden = document.getElementById(input.dataset.target);
        input.parentElement.querySelector('.hasil-pilih')?.remove();

        window.SaranOrang.pasang(input, {
            garis: (input.dataset.garis || '').split(',').filter(Boolean),
            saatKetik: () => {
                if (hidden.value !== '') {
                    hidden.value = '';
                    hidden.dispatchEvent(new Event('change'));
                }
            },
            pilih: (r) => {
                hidden.value = r.id;
                hidden.dataset.generasi = r.generasi_ke;
                hidden.dataset.garis = r.garis;
                input.value = `${r.nama_lengkap} · ${r.kode_anggota}`;
                hidden.dispatchEvent(new Event('change'));
            },
        });
    });
})();
