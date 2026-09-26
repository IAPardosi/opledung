/**
 * Form pendaftaran kepala keluarga: generasi antara, anak-anak, sundut pendaftar,
 * dan daftar calon validator keluarga (dimuat ulang setiap leluhur/generasi berubah).
 */
(function () {
    'use strict';
    const form = document.getElementById('formDaftar');
    const batas = Number(form.dataset.batas);
    const maks = Number(form.dataset.maks);
    const leluhur = document.getElementById('leluhur_id');
    const wadah = document.getElementById('antara');
    const info = document.getElementById('infoLeluhur');
    const infoSundut = document.getElementById('infoSundut');
    const tombol = document.getElementById('tambahAntara');
    const kandidatEl = document.getElementById('kandidatValidator');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    function barisAntara() {
        const el = document.createElement('div');
        el.className = 'row g-2 align-items-center mb-2 antara-baris';
        el.innerHTML = `
            <div class="col-auto"><span class="chip chip-gelap sundut">Sundut ?</span></div>
            <div class="col"><input class="form-control form-control-sm" name="antara_nama[]" placeholder="Nama lengkap (laki-laki)" maxlength="150" required aria-label="Nama"></div>
            <div class="col-3 col-md-2"><input class="form-control form-control-sm" name="antara_tahun[]" type="number" min="1500" max="2100" placeholder="Thn lahir" aria-label="Tahun lahir"></div>
            <div class="col-3 col-md-2"><select class="form-select form-select-sm" name="antara_hidup[]" aria-label="Status"><option value="">Status?</option><option value="hidup">Hidup</option><option value="meninggal">Meninggal</option></select></div>
            <div class="col-auto"><button type="button" class="btn btn-sm btn-link text-danger hapus" aria-label="Hapus"><i class="bi bi-x-lg"></i></button></div>`;
        el.querySelector('.hapus').addEventListener('click', () => { el.remove(); hitung(); });
        wadah.append(el);
        hitung();
        el.querySelector('input').focus();
    }

    function barisAnak() {
        const el = document.createElement('div');
        el.className = 'row g-2 align-items-center mb-2';
        el.innerHTML = `
            <div class="col"><input class="form-control form-control-sm" name="anak_nama[]" placeholder="Nama anak" maxlength="150" required aria-label="Nama anak"></div>
            <div class="col-3 col-md-2"><select class="form-select form-select-sm" name="anak_jk[]" required aria-label="Jenis kelamin"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
            <div class="col-3 col-md-2"><input class="form-control form-control-sm" name="anak_tahun[]" type="number" min="1900" max="2100" placeholder="Thn lahir" aria-label="Tahun lahir"></div>
            <div class="col-auto"><button type="button" class="btn btn-sm btn-link text-danger" aria-label="Hapus"><i class="bi bi-x-lg"></i></button></div>`;
        el.querySelector('button').addEventListener('click', () => el.remove());
        document.getElementById('daftarAnak').append(el);
        el.querySelector('input').focus();
    }

    let timerValidator;
    function muatValidator() {
        clearTimeout(timerValidator);
        if (!leluhur.value) { kandidatEl.textContent = 'Pilih leluhur terlebih dahulu.'; return; }
        timerValidator = setTimeout(async () => {
            const antara = wadah.querySelectorAll('.antara-baris').length;
            const rows = await (await fetch(`${form.dataset.validatorApi}?leluhur=${leluhur.value}&antara=${antara}`)).json();
            kandidatEl.innerHTML = rows.length === 0
                ? '<div class="alert alert-light border mb-0"><i class="bi bi-info-circle"></i> Belum ada orang tua/ompung Anda yang menjadi member. Penatua punguan akan memeriksa pendaftaran ini lebih teliti.</div>'
                : '<div class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(230px, 1fr))">' + rows.map((r, i) => `
                    <label class="kartu-orang" style="cursor:pointer" for="val_${r.user_id}">
                        <input class="form-check-input mt-0" type="radio" name="validator_user_id" id="val_${r.user_id}" value="${r.user_id}" ${i === 0 ? 'checked' : ''} required>
                        <span><span class="n d-block">${esc(r.nama_lengkap)}</span><span class="k">${esc(r.hubungan)} · Sundut ${r.generasi_ke} · @${esc(r.username)}</span></span>
                    </label>`).join('') + '</div>';
        }, 200);
    }

    function hitung() {
        const g = Number(leluhur.dataset.generasi || 0);
        const boru = leluhur.dataset.garis === 'boru';
        const rows = [...wadah.querySelectorAll('.antara-baris')];
        rows.forEach((r, i) => { r.querySelector('.sundut').textContent = g ? `Sundut ${g + i + 1}` : `Generasi ${i + 1}`; });
        tombol.disabled = boru || rows.length >= maks;

        if (leluhur.value) {
            const diri = g + rows.length + 1;
            info.innerHTML = g + 1 <= batas
                ? `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Sundut ${g} termasuk Silsilah Pokok. Pilih leluhur mulai Sundut ${batas}, atau hubungi Ketua Adat.</span>`
                : `<span class="text-success"><i class="bi bi-check-circle"></i> Leluhur di Sundut ${g}${boru ? ' (boru: Anda tercatat sebagai anak boru)' : ''}.</span>`;
            infoSundut.innerHTML = `Anda akan tercatat di <b>Sundut ${diri}</b>${boru ? ' sebagai anak boru' : ''}. Anak-anak Anda di Sundut ${diri + 1}.`;
            if (boru) wadah.innerHTML = '';
        } else {
            info.innerHTML = '';
            infoSundut.textContent = 'Pilih leluhur terlebih dahulu untuk melihat sundut Anda.';
        }
        muatValidator();
    }

    tombol.addEventListener('click', barisAntara);
    document.getElementById('tambahAnak').addEventListener('click', barisAnak);
    leluhur.addEventListener('change', hitung);

    // "Ini saya": buka halaman konfirmasi klaim.
    const klaimId = document.getElementById('klaim_id');
    const tombolKlaim = document.getElementById('tombolKlaim');
    klaimId.addEventListener('change', () => {
        const ada = Boolean(klaimId.value);
        tombolKlaim.classList.toggle('disabled', !ada);
        tombolKlaim.setAttribute('aria-disabled', String(!ada));
        tombolKlaim.href = ada ? `${form.dataset.klaim}/${klaimId.value}` : '#';
    });
})();
