/**
 * Saran pencarian anggota: kartu berfoto dengan sundut, "anak ni …", huta, domisili, dan punguan.
 * Bisa dipakai dengan papan ketik (↑ ↓ Enter Esc). Data dari /api/cari (member mendapat foto & huta).
 *
 *   SaranOrang.pasang(input, {href: (r) => url})            // saran berupa tautan
 *   SaranOrang.pasang(input, {pilih: (r) => {...}, garis})  // saran memilih nilai
 */
(function () {
    'use strict';

    const base = window.SILSILAH_URL || (window.SILSILAH && window.SILSILAH.baseUrl) || '/';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const inisial = (n) => n.replace(/\bbr\.?\s/i, '').split(/\s+/).filter(Boolean).slice(0, 2).map((k) => k[0]).join('').toUpperCase();
    const labelGaris = {utama: 'Anak', boru: 'Boru', anak_boru: 'Anak boru'};
    let nomor = 0;

    function sorot(teks, q) {
        const kata = q.toLowerCase().split(/\s+/).filter((k) => k.length >= 2);
        let html = esc(teks);
        kata.forEach((k) => {
            const i = html.toLowerCase().indexOf(k);
            if (i >= 0 && !/[<>&]/.test(html.slice(i, i + k.length))) {
                html = html.slice(0, i) + '<mark>' + html.slice(i, i + k.length) + '</mark>' + html.slice(i + k.length);
            }
        });
        return html;
    }

    function kartu(r, q) {
        const foto = r.foto
            ? `<img src="${esc(r.foto)}" alt="" class="saran-foto garis-cincin-${esc(r.garis)}" loading="lazy">`
            : `<span class="saran-foto garis-cincin-${esc(r.garis)}" aria-hidden="true">${esc(inisial(r.nama_lengkap))}</span>`;
        const tempat = [r.huta ? 'Huta ' + r.huta : null, r.domisili ? 'Domisili ' + r.domisili : null].filter(Boolean).join(' · ');
        return `${foto}
            <span class="saran-isi">
                <span class="saran-baris1"><span class="saran-nama">${sorot(r.nama_lengkap, q)}${r.status_hidup === 'meninggal' ? ' <span class="text-teks-3">†</span>' : ''}</span>
                    <span class="saran-sundut"><span class="penanda penanda-${esc(r.garis)}"></span>S${r.generasi_ke} · ${labelGaris[r.garis] || ''}</span></span>
                ${r.gelar_adat ? `<span class="saran-gelar">${esc(r.gelar_adat)}</span>` : ''}
                ${r.keterangan ? `<span class="saran-ket${r.kembar ? ' pembeda' : ''}">${esc(r.keterangan)}${r.pasangan && r.garis === 'boru' ? ' · suami ' + esc(r.pasangan) : ''}</span>` : ''}
                ${tempat || r.punguan ? `<span class="saran-tempat">${esc(tempat)}${r.punguan ? `<span class="saran-punguan">Member ${esc(r.punguan)}</span>` : ''}</span>` : ''}
                <span class="saran-kode">${esc(r.kode_anggota)}</span>
            </span>`;
    }

    function pasang(input, opsi) {
        opsi = opsi || {};
        const id = 'saran-' + (++nomor);
        const wadah = input.parentElement;
        wadah.classList.add('saran-wadah');
        let daftar = wadah.querySelector('.saran-daftar');
        if (!daftar) {
            daftar = document.createElement('div');
            daftar.className = 'saran-daftar';
            wadah.appendChild(daftar);
        }
        daftar.id = id;
        daftar.setAttribute('role', 'listbox');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-controls', id);
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('autocomplete', 'off');

        let rows = [], aktif = -1, timer, permintaan = 0;

        function tutup() {
            daftar.innerHTML = '';
            daftar.classList.remove('buka');
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            aktif = -1;
        }

        function tandai(i) {
            aktif = i;
            daftar.querySelectorAll('.saran-item').forEach((el, j) => el.classList.toggle('aktif', j === i));
            const el = daftar.querySelectorAll('.saran-item')[i];
            if (el) {
                input.setAttribute('aria-activedescendant', el.id);
                el.scrollIntoView({block: 'nearest'});
            }
        }

        function pilih(i) {
            const r = rows[i];
            if (!r) return;
            if (opsi.href) {
                window.location.href = opsi.href(r);
                return;
            }
            opsi.pilih && opsi.pilih(r);
            tutup();
        }

        function tampil(q) {
            daftar.classList.add('buka');
            input.setAttribute('aria-expanded', 'true');
            if (rows.length === 0) {
                daftar.innerHTML = `<div class="saran-kosong">Tidak ditemukan. Coba nama ayah: <b>anak ni …</b>, kode anggota, atau nama + kota.</div>`;
                return;
            }
            const kembar = rows.some((r) => r.kembar);
            daftar.innerHTML = rows.map((r, i) => opsi.href
                ? `<a class="saran-item" role="option" id="${id}-${i}" href="${esc(opsi.href(r))}" data-i="${i}">${kartu(r, q)}</a>`
                : `<button type="button" class="saran-item" role="option" id="${id}-${i}" data-i="${i}">${kartu(r, q)}</button>`).join('')
                + `<div class="saran-kaki"><span>${kembar ? 'Nama sama dibedakan oleh ayah, sundut & huta' : rows.length + ' saran'}</span><span class="d-none d-md-inline">↑↓ pilih · Enter buka · Esc tutup</span></div>`;
        }

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            opsi.saatKetik && opsi.saatKetik();
            if (q.length < 2) {
                tutup();
                return;
            }
            timer = setTimeout(async () => {
                const ke = ++permintaan;
                try {
                    const res = await fetch(`${base}api/cari?q=${encodeURIComponent(q)}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                    let data = await res.json();
                    if (ke !== permintaan) return;
                    if (opsi.garis && opsi.garis.length) data = data.filter((r) => opsi.garis.includes(r.garis));
                    rows = data;
                    aktif = -1;
                    tampil(q);
                } catch (e) { /* jaringan: biarkan pengguna mengetik ulang */ }
            }, 220);
        });

        input.addEventListener('keydown', (e) => {
            if (!daftar.classList.contains('buka')) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); tandai(Math.min(rows.length - 1, aktif + 1)); }
            if (e.key === 'ArrowUp') { e.preventDefault(); tandai(Math.max(0, aktif - 1)); }
            if (e.key === 'Enter' && aktif >= 0) { e.preventDefault(); pilih(aktif); }
            if (e.key === 'Escape') { tutup(); }
        });

        daftar.addEventListener('click', (e) => {
            const el = e.target.closest('.saran-item');
            if (!el || opsi.href) return;
            pilih(Number(el.dataset.i));
        });

        document.addEventListener('click', (e) => {
            if (!wadah.contains(e.target)) tutup();
        });
    }

    window.SaranOrang = {pasang};

    // Otomatis: <input data-saran="profil|mapping|pohon">
    document.querySelectorAll('input[data-saran]').forEach((input) => {
        const tujuan = {profil: 'anggota/', mapping: 'mapping/', pohon: 'silsilah/'}[input.dataset.saran] || 'anggota/';
        pasang(input, {href: (r) => base + tujuan + r.id});
    });
})();
