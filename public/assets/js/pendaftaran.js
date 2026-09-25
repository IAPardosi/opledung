/**
 * Form pendaftaran silsilah: baris generasi antara dan perhitungan sundut pendaftar.
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

    function baris() {
        const i = wadah.children.length;
        const el = document.createElement('div');
        el.className = 'row g-2 align-items-center mb-2 antara-baris';
        el.innerHTML = `
            <div class="col-auto"><span class="badge text-bg-dark sundut">Sundut ?</span></div>
            <div class="col"><input class="form-control form-control-sm" name="antara_nama[]" placeholder="Nama lengkap (laki-laki)" maxlength="150" required></div>
            <div class="col-3 col-md-2"><input class="form-control form-control-sm" name="antara_tahun[]" type="number" min="1500" max="2100" placeholder="Thn lahir"></div>
            <div class="col-3 col-md-2"><select class="form-select form-select-sm" name="antara_hidup[]"><option value="">Status?</option><option value="hidup">Hidup</option><option value="meninggal">Meninggal</option></select></div>
            <div class="col-auto"><button type="button" class="btn btn-sm btn-link text-danger hapus" aria-label="Hapus"><i class="bi bi-x-lg"></i></button></div>`;
        el.querySelector('.hapus').addEventListener('click', () => { el.remove(); hitung(); });
        wadah.append(el);
        hitung();
        el.querySelector('input').focus();
    }

    function hitung() {
        const g = Number(leluhur.dataset.generasi || 0);
        const boru = leluhur.dataset.garis === 'boru';
        const rows = [...wadah.querySelectorAll('.antara-baris')];
        rows.forEach((r, i) => { r.querySelector('.sundut').textContent = g ? `Sundut ${g + i + 1}` : `Generasi ${i + 1}`; });
        tombol.disabled = boru || rows.length >= maks;

        if (!leluhur.value) {
            info.innerHTML = '';
            infoSundut.textContent = 'Pilih leluhur terlebih dahulu untuk melihat sundut Anda.';
            return;
        }
        const diri = g + rows.length + 1;
        if (g + 1 <= batas) {
            info.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Sundut ${g} termasuk Silsilah Pokok. Pilih leluhur mulai Sundut ${batas}, atau hubungi Ketua Adat.</span>`;
        } else {
            info.innerHTML = `<span class="text-success"><i class="bi bi-check-circle"></i> Leluhur di Sundut ${g}${boru ? ' (boru — Anda tercatat sebagai anak boru)' : ''}.</span>`;
        }
        infoSundut.innerHTML = `Anda akan tercatat di <b>Sundut ${diri}</b>${boru ? ' sebagai anak boru' : ''}.`;
        if (boru) wadah.innerHTML = '';
    }

    tombol.addEventListener('click', baris);
    leluhur.addEventListener('change', hitung);

    // Klaim "Ini saya"
    const klaimId = document.getElementById('klaim_id');
    const tombolKlaim = document.getElementById('tombolKlaim');
    klaimId.addEventListener('change', () => { tombolKlaim.disabled = !klaimId.value; });
    document.getElementById('formKlaim').addEventListener('submit', (e) => {
        if (!klaimId.value || !confirm('Ajukan data ini sebagai diri Anda?')) { e.preventDefault(); return; }
        e.target.action = `${window.SILSILAH_URL}pendaftaran/klaim/${klaimId.value}`;
    });
})();
