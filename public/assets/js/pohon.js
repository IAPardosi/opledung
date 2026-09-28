/**
 * Pohon silsilah interaktif (D3 v7), dipakai halaman Pohon cabang dan Mapping keturunan.
 * Data dimuat bertahap: node yang punya anak belum dimuat akan mengambil
 * /api/pohon/{id} saat dibuka. Pada mapping, node membawa 'tersembunyi' (jumlah anak
 * yang dilipat, tampil sebagai +N) serta 'di_jalur' dan 'target' untuk menyorot jalur.
 */
(function () {
    'use strict';

    const S = window.SILSILAH;
    const MAPPING = S.mapping || null;
    const urlCari = S.urlCari || 'silsilah/';

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    }

    // Pencarian: buka orang yang dipilih sebagai pusat pohon atau mapping.
    const input = document.getElementById('cariPohon');
    if (input && window.SaranOrang) {
        document.getElementById('hasilCari')?.remove();
        const akhiran = MAPPING ? `?mode=${encodeURIComponent(MAPPING.mode)}` : '';
        window.SaranOrang.pasang(input, {href: (r) => `${S.baseUrl}${urlCari}${r.id}${akhiran}`});
    }

    if (!S.data) return;

    const LEBAR = 210, TINGGI = 52, BARIS = 17, JARAK_X = 260, JARAK_Y = 64;
    // Kotak keluarga: tinggi bertambah satu baris per pasangan (maks. 3 baris + "+N lagi").
    const tinggi = (d) => {
        const n = d && d.data && d.data.pasangan ? d.data.pasangan.length : 0;
        return TINGGI + BARIS * Math.min(n, 3) + (n > 3 ? 14 : 0);
    };
    const isiKartu = {utama: '#ffffff', boru: '#fbf3df', anak_boru: '#f6f0e8'};
    const css = getComputedStyle(document.documentElement);
    const warna = {
        utama: css.getPropertyValue('--garis-utama').trim(),
        boru: css.getPropertyValue('--garis-boru').trim(),
        anak_boru: css.getPropertyValue('--garis-anak_boru').trim(),
    };

    const svg = d3.select('#pohon');
    const g = svg.append('g');
    const zoom = d3.zoom().scaleExtent([0.1, 2.5]).on('zoom', (e) => g.attr('transform', e.transform));
    svg.call(zoom).on('dblclick.zoom', null);

    // Konversi data API ({anak: [...]}) ke struktur yang dipakai d3.hierarchy.
    function siapkan(node) {
        node._muat = 'tersembunyi' in node
            ? node.tersembunyi === 0
            : (node.anak.length > 0 || !node.punya_anak);
        node.anak.forEach(siapkan);
        return node;
    }

    const root = d3.hierarchy(siapkan(S.data), (d) => d.anak);
    root.x0 = 0;
    root.y0 = 0;
    // Pohon cabang: awal tampil 2 generasi di bawah akar. Mapping: tampil sesuai mode.
    if (!MAPPING) {
        root.descendants().forEach((d) => {
            if (d.depth >= 2 && d.children) {
                d._children = d.children;
                d.children = null;
            }
        });
    }

    const target = MAPPING ? root.descendants().find((d) => d.data.id === MAPPING.target) : null;
    let terpilih = (target || root).data.id;
    // Arah pohon: 'kanan' (sundut mengalir ke kanan) atau 'bawah' (sundut menurun).
    let arah = MAPPING && MAPPING.mode === 'fokus' ? 'bawah' : 'kanan';
    try {
        arah = localStorage.getItem('arahPohon' + (MAPPING ? MAPPING.mode : '')) || arah;
    } catch (e) { /* penyimpanan tidak tersedia */ }
    let tree;
    const pos = (x, y) => arah === 'kanan' ? `translate(${y},${x})` : `translate(${x - LEBAR / 2},${y})`;
    const posToggle = (d) => arah === 'kanan' ? `translate(${LEBAR},0)` : `translate(${LEBAR / 2},${tinggi(d) / 2})`;
    function aturArah() {
        tree = arah === 'kanan'
            ? d3.tree().nodeSize([JARAK_Y, JARAK_X]).separation((a, b) => (a.parent === b.parent ? 1 : 1.2) * ((tinggi(a) + tinggi(b)) / 2 + 12) / JARAK_Y)
            : d3.tree().nodeSize([LEBAR + 22, 150]).separation((a, b) => (a.parent === b.parent ? 1 : 1.15));
    }
    aturArah();

    function punyaTersembunyi(d) {
        return !!d._children || (!d.data._muat && d.data.punya_anak);
    }

    function teksToggle(d) {
        if (!d.data._muat && d.data.tersembunyi > 0) return `+${d.data.tersembunyi}`;
        return d.children ? '−' : '+';
    }

    function render(sumber) {
        tree(root);
        const nodes = root.descendants();
        const links = root.links();
        const durasi = 250;

        const link = g.selectAll('path.link').data(links, (d) => d.target.data.id);
        link.enter().insert('path', 'g')
            .attr('class', 'link')
            .attr('d', () => diagonal({x: sumber.x0, y: sumber.y0}, {x: sumber.x0, y: sumber.y0}))
            .merge(link)
            .classed('jalur', (d) => !!(d.source.data.di_jalur && d.target.data.di_jalur))
            .transition().duration(durasi)
            .attr('d', (d) => diagonal(d.source, d.target));
        link.exit().transition().duration(durasi)
            .attr('d', () => diagonal({x: sumber.x, y: sumber.y}, {x: sumber.x, y: sumber.y}))
            .remove();
        g.selectAll('path.link.jalur').raise();
        g.selectAll('g.node').raise();

        const node = g.selectAll('g.node').data(nodes, (d) => d.data.id);
        const masuk = node.enter().append('g')
            .attr('class', 'node')
            .classed('di-jalur', (d) => !!d.data.di_jalur)
            .classed('target', (d) => !!d.data.target)
            .attr('transform', pos(sumber.x0, sumber.y0))
            .on('click', (e, d) => pilih(d));

        masuk.append('rect').attr('class', 'kartu')
            .attr('x', 0).attr('y', (d) => -tinggi(d) / 2).attr('width', LEBAR).attr('height', tinggi)
            .attr('rx', 10)
            .attr('fill', (d) => isiKartu[d.data.garis] || '#fff')
            .attr('stroke', (d) => warna[d.data.garis] || '#999');
        masuk.append('rect')
            .attr('x', 0).attr('y', (d) => -tinggi(d) / 2).attr('width', 6).attr('height', tinggi)
            .attr('fill', (d) => warna[d.data.garis] || '#999');
        // Penanda bentuk (tidak hanya warna): ■ anak, ◆ boru, ● anak boru.
        masuk.append('path').attr('class', 'penanda')
            .attr('transform', (d) => `translate(19,${-tinggi(d) / 2 + 17})`)
            .attr('d', (d) => d.data.garis === 'boru' ? 'M0 -5 L5 0 L0 5 L-5 0 Z' : (d.data.garis === 'anak_boru' ? 'M-4 0 a4 4 0 1 0 8 0 a4 4 0 1 0 -8 0' : 'M-4 -4 H4 V4 H-4 Z'))
            .attr('fill', (d) => warna[d.data.garis] || '#999');
        masuk.append('text').attr('class', 'nama').attr('x', 30).attr('y', (d) => -tinggi(d) / 2 + 21)
            .text((d) => potong(d.data.nama_lengkap, 25));
        masuk.append('text').attr('class', 'sub').attr('x', 14).attr('y', (d) => -tinggi(d) / 2 + 39)
            .text((d) => `G${d.data.generasi_ke} · ${d.data.kode_anggota}${d.data.status_hidup === 'meninggal' ? ' · †' : ''}${d.data.ibu_ke ? ' · dari istri ' + d.data.ibu_ke : ''}`);
        masuk.each(function (d) {
            const ps = d.data.pasangan || [];
            const g0 = d3.select(this);
            const y0 = -tinggi(d) / 2 + 39;
            ps.slice(0, 3).forEach((p, i) => {
                const y = y0 + BARIS * (i + 1);
                g0.append('circle').attr('class', 'penanda-pasangan').attr('cx', 19).attr('cy', y - 4).attr('r', 3.5);
                const peran = d.data.jenis_kelamin === 'P' ? 'Suami' : (ps.length > 1 ? `Istri ${p.ke}` : 'Istri');
                g0.append('text').attr('class', 'pasangan').attr('x', 30).attr('y', y)
                    .text(potong(`${peran} · ${p.nama}${p.status_hidup === 'meninggal' ? ' †' : ''}`, 30));
            });
            if (ps.length > 3) {
                g0.append('text').attr('class', 'pasangan').attr('x', 30).attr('y', y0 + BARIS * 3 + 14).text(`+${ps.length - 3} pasangan lagi`);
            }
        });

        const tombol = masuk.append('g').attr('class', 'toggle')
            .attr('transform', posToggle)
            .on('click', (e, d) => {
                e.stopPropagation();
                buka(d);
            });
        tombol.append('rect').attr('rx', 9).attr('height', 18).attr('y', -9).attr('fill', '#fff').attr('stroke', '#bdb3a8');
        tombol.append('text').attr('text-anchor', 'middle').attr('dy', 4).attr('font-size', 12);

        const semua = masuk.merge(node);
        semua.transition().duration(durasi).attr('transform', (d) => pos(d.x, d.y));
        semua.select('g.toggle').attr('transform', posToggle);
        semua.classed('terpilih', (d) => d.data.id === terpilih);
        const tg = semua.select('g.toggle')
            .style('display', (d) => (d.children || punyaTersembunyi(d)) ? null : 'none');
        tg.select('text').text(teksToggle);
        tg.select('rect')
            .attr('width', (d) => Math.max(18, teksToggle(d).length * 7 + 8))
            .attr('x', (d) => -Math.max(18, teksToggle(d).length * 7 + 8) / 2);
        tg.attr('aria-label', (d) => (!d.data._muat && d.data.tersembunyi > 0) ? `${d.data.tersembunyi} anak belum dibuka` : null);

        node.exit().transition().duration(durasi)
            .attr('transform', pos(sumber.x, sumber.y)).remove();

        nodes.forEach((d) => {
            d.x0 = d.x;
            d.y0 = d.y;
        });
    }

    function diagonal(s, t) {
        if (arah === 'bawah') {
            const sy = s.y + tinggi(s) / 2, ty = t.y - tinggi(t) / 2;
            return `M${s.x},${sy} C${s.x},${(sy + ty) / 2} ${t.x},${(sy + ty) / 2} ${t.x},${ty}`;
        }
        const sx = s.y + LEBAR, tx = t.y;
        return `M${sx},${s.x} C${(sx + tx) / 2},${s.x} ${(sx + tx) / 2},${t.x} ${tx},${t.x}`;
    }

    // Anak yang dimuat dari API digabung dengan anak yang sudah tampil (mis. jalur) agar tidak hilang.
    async function muatAnak(d) {
        const res = await fetch(`${S.baseUrl}api/pohon/${d.data.id}?kedalaman=${MAPPING ? 1 : 2}`);
        if (!res.ok) throw new Error('gagal');
        const data = siapkan(await res.json());
        const lama = new Map((d.children || d._children || []).map((c) => [c.data.id, c]));
        d.data.anak = data.anak;
        d.data._muat = true;
        d.data.tersembunyi = 0;
        d.children = data.anak.map((a) => {
            if (lama.has(a.id)) return lama.get(a.id);
            const h = d3.hierarchy(a, (x) => x.anak);
            h.descendants().forEach((c) => {
                c.depth += d.depth + 1;
                if (c.children) {
                    c._children = c.children;
                    c.children = null;
                }
            });
            h.parent = d;
            return h;
        });
        d._children = null;
    }

    async function buka(d) {
        if (!d.data._muat && d.data.punya_anak) {
            try {
                await muatAnak(d);
            } catch (err) {
                alert('Gagal memuat data. Periksa koneksi lalu coba lagi.');
                return;
            }
        } else if (d.children) {
            d._children = d.children;
            d.children = null;
        } else if (d._children) {
            d.children = d._children;
            d._children = null;
        }
        render(d);
    }

    function pilih(d) {
        terpilih = d.data.id;
        g.selectAll('g.node').classed('terpilih', (n) => n.data.id === terpilih);
        const p = d.data;
        const url = (path) => S.baseUrl + path;
        const hidup = p.status_hidup === 'meninggal' ? 'Meninggal' : (p.status_hidup === 'hidup' ? 'Hidup' : 'Tidak diketahui');
        const modeQ = MAPPING ? `?mode=${encodeURIComponent(MAPPING.mode)}` : '';
        document.getElementById('panelOrang').innerHTML = `
            <div class="card-body">
                <span class="badge badge-garis garis-${esc(p.garis)} mb-2">${esc(S.label[p.garis] || p.garis)}</span>
                ${p.di_jalur ? '<span class="badge text-bg-danger mb-2">Di jalur</span>' : ''}
                <h2 class="h5 mb-0">${esc(p.nama_lengkap)}</h2>
                ${p.gelar_adat ? `<div class="small text-body-secondary">${esc(p.gelar_adat)}</div>` : ''}
                <dl class="data-profil mt-3 mb-3">
                    <dt>Kode anggota</dt><dd class="font-monospace small">${esc(p.kode_anggota)}</dd>
                    <dt>Generasi</dt><dd>${p.generasi_ke}</dd>
                    <dt>Status</dt><dd>${hidup}</dd>
                    ${p.jumlah_anak !== undefined ? `<dt>Jumlah anak</dt><dd>${p.jumlah_anak}</dd>` : ''}
                    ${(p.pasangan || []).map((ps) => `<dt>${p.jenis_kelamin === 'P' ? 'Suami' : ((p.pasangan.length > 1) ? 'Istri ' + ps.ke : 'Istri')}</dt><dd>${esc(ps.nama)}${ps.status_hidup === 'meninggal' ? ' †' : ''}</dd>`).join('')}
                </dl>
                <div id="tuturPanel"></div>
                <div class="d-grid gap-2">
                    <a class="btn btn-utama btn-sm" href="${url('anggota/' + p.id)}">
                        ${S.login ? '<i class="bi bi-person-vcard"></i> Lihat profil' : '<i class="bi bi-lock"></i> Masuk untuk lihat profil'}
                    </a>
                    ${p.garis !== 'anak_boru' && !(MAPPING && p.target) ? `<a class="btn btn-outline-secondary btn-sm" href="${url('mapping/' + p.id + modeQ)}"><i class="bi bi-diagram-2"></i> Mapping orang ini</a>` : ''}
                    ${p.garis !== 'anak_boru' ? `<a class="btn btn-outline-secondary btn-sm" href="${url('silsilah/' + p.id)}"><i class="bi bi-bullseye"></i> Jadikan pusat pohon</a>` : ''}
                </div>
            </div>`;
        muatTutur(p.id);
    }

    // Partuturan pengguna yang login kepada orang terpilih.
    async function muatTutur(id) {
        if (!S.login) return;
        try {
            const res = await fetch(`${S.baseUrl}api/partuturan/${id}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
            if (!res.ok || terpilih !== id) return;
            const t = await res.json();
            const el = document.getElementById('tuturPanel');
            if (!el) return;
            el.innerHTML = t.tersedia
                ? `<div class="tutur mb-3"><div class="isi py-3">
                        <div class="label">Anda memanggil</div><div class="sebutan" style="font-size:1.6rem">${esc(t.sebutan)}</div>
                        <div class="ket small">Ia memanggil Anda: <b class="text-emas-terang">${esc(t.balik)}</b></div>
                        ${t.keterangan ? `<div class="ket small mt-2">${esc(t.keterangan)}</div>` : ''}
                        ${t.dalihan ? `<div class="mt-2"><span class="chip-dnt aktif">${esc(t.dalihan.nama)}</span><div class="ket small mt-1 fst-italic">${esc(t.dalihan.semboyan)}</div></div>` : ''}
                    </div></div>`
                : `<p class="small text-teks-2">${esc(t.pesan)}</p>`;
        } catch (e) { /* panel partuturan bersifat tambahan */ }
    }

    function potong(teks, n) {
        return teks.length > n ? teks.slice(0, n - 1) + '…' : teks;
    }

    function posisiAwal() {
        const box = svg.node().getBoundingClientRect();
        const kecil = box.width < 600;
        if (target) {
            const b = batas();
            if (Math.min((box.width - 40) / b.width, (box.height - 40) / b.height) >= (kecil ? 0.45 : 0.6)) {
                lihatSemua();
                return;
            }
            const skala = kecil ? 0.55 : 0.85;
            if (arah === 'bawah') {
                // Orang terpilih di bagian bawah, leluhur di atasnya.
                svg.transition().duration(300).call(zoom.transform, d3.zoomIdentity
                    .translate(box.width / 2 - target.x * skala, box.height - (tinggi(target) / 2 + 60) * skala - target.y * skala).scale(skala));
                return;
            }
            // Orang terpilih di sisi kanan, jalur ke leluhur mengalir ke kiri.
            const x = box.width - (LEBAR + (kecil ? 30 : 90)) * skala - target.y * skala;
            svg.transition().duration(300).call(zoom.transform, d3.zoomIdentity.translate(x, box.height / 2 - target.x * skala).scale(skala));
            return;
        }
        svg.transition().duration(300).call(zoom.transform, d3.zoomIdentity.translate(kecil ? 12 : 40, box.height / 2).scale(kecil ? 0.55 : 0.9));
    }

    // Batas tampilan dihitung dari posisi akhir node (bukan DOM yang masih bertransisi).
    function batas() {
        const n = root.descendants();
        const xs = n.map((d) => arah === 'kanan' ? d.y : d.x - LEBAR / 2);
        const ys = n.map((d) => (arah === 'kanan' ? d.x : d.y) - tinggi(d) / 2);
        const ye = n.map((d) => (arah === 'kanan' ? d.x : d.y) + tinggi(d) / 2);
        const x = Math.min(...xs), y = Math.min(...ys);
        return {x, y, width: Math.max(...xs) + LEBAR + 20 - x, height: Math.max(...ye) + 20 - y};
    }

    function lihatSemua() {
        const box = svg.node().getBoundingClientRect();
        const b = batas();
        const skala = Math.min(1, (box.width - 40) / b.width, (box.height - 40) / b.height);
        svg.transition().duration(300).call(zoom.transform, d3.zoomIdentity
            .translate(box.width / 2 - (b.x + b.width / 2) * skala, box.height / 2 - (b.y + b.height / 2) * skala).scale(skala));
    }

    document.getElementById('zoomMasuk').addEventListener('click', () => svg.transition().call(zoom.scaleBy, 1.25));
    document.getElementById('zoomKeluar').addEventListener('click', () => svg.transition().call(zoom.scaleBy, 0.8));
    document.getElementById('zoomReset').addEventListener('click', posisiAwal);
    document.getElementById('zoomSemua')?.addEventListener('click', () => setTimeout(lihatSemua, 0));
    document.getElementById('putarArah')?.addEventListener('click', () => {
        arah = arah === 'kanan' ? 'bawah' : 'kanan';
        try {
            localStorage.setItem('arahPohon' + (MAPPING ? MAPPING.mode : ''), arah);
        } catch (e) { /* abaikan */ }
        aturArah();
        g.selectAll('*').remove();
        root.descendants().forEach((d) => { d.x0 = d.x; d.y0 = d.y; });
        render(root);
        setTimeout(posisiAwal, 0);
    });

    render(root);
    pilih(target || root);
    // Setelah transisi awal selesai, posisi node sudah final.
    setTimeout(posisiAwal, 0);
})();
