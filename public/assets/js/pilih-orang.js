/**
 * Kotak pencarian anggota: <input class="pilih-orang" data-target="idHidden"> diikuti .hasil-pilih.
 * Memakai /api/cari (hanya kolom publik). Opsi data-garis="utama,boru" membatasi hasil.
 */
(function () {
    'use strict';
    const base = window.SILSILAH_URL || '/';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    document.querySelectorAll('input.pilih-orang').forEach((input) => {
        const hasil = input.parentElement.querySelector('.hasil-pilih');
        const hidden = document.getElementById(input.dataset.target);
        const garis = (input.dataset.garis || '').split(',').filter(Boolean);
        let timer;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            hidden.value = '';
            hidden.dispatchEvent(new Event('change'));
            const q = input.value.trim();
            if (q.length < 2) { hasil.innerHTML = ''; return; }
            timer = setTimeout(async () => {
                let rows = await (await fetch(`${base}api/cari?q=${encodeURIComponent(q)}`)).json();
                if (garis.length) rows = rows.filter((r) => garis.includes(r.garis));
                hasil.innerHTML = rows.length === 0
                    ? '<div class="list-group-item small text-body-secondary">Tidak ditemukan.</div>'
                    : rows.map((r) => `<button type="button" class="list-group-item list-group-item-action small text-start" data-id="${r.id}" data-generasi="${r.generasi_ke}" data-garis="${esc(r.garis)}" data-label="${esc(r.nama_lengkap + ' · ' + r.kode_anggota)}">
                        <div class="fw-semibold">${esc(r.nama_lengkap)} <span class="text-body-secondary fw-normal">· Sundut ${r.generasi_ke}</span></div>
                        <div class="text-body-secondary">${esc(r.kode_anggota)}${r.nama_induk ? ' · anak dari ' + esc(r.nama_induk) : ''}</div></button>`).join('');
            }, 250);
        });

        hasil.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-id]');
            if (!btn) return;
            hidden.value = btn.dataset.id;
            hidden.dataset.generasi = btn.dataset.generasi;
            hidden.dataset.garis = btn.dataset.garis;
            input.value = btn.dataset.label;
            hasil.innerHTML = '';
            hidden.dispatchEvent(new Event('change'));
        });

        document.addEventListener('click', (e) => {
            if (!input.parentElement.contains(e.target)) hasil.innerHTML = '';
        });
    });
})();
