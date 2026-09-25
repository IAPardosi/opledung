/**
 * Pohon silsilah interaktif (D3 v7).
 * Data dimuat bertahap: node yang punya_anak tetapi belum dimuat akan mengambil
 * /api/pohon/{id}?kedalaman=2 saat dibuka.
 */
(function () {
    'use strict';

    const S = window.SILSILAH;
    const LEBAR = 190, TINGGI = 52, JARAK_X = 240, JARAK_Y = 64;
    const warna = {
        utama: getComputedStyle(document.documentElement).getPropertyValue('--garis-utama').trim(),
        boru: getComputedStyle(document.documentElement).getPropertyValue('--garis-boru').trim(),
        anak_boru: getComputedStyle(document.documentElement).getPropertyValue('--garis-anak_boru').trim(),
    };

    const svg = d3.select('#pohon');
    const g = svg.append('g');
    const zoom = d3.zoom().scaleExtent([0.15, 2.5]).on('zoom', (e) => g.attr('transform', e.transform));
    svg.call(zoom).on('dblclick.zoom', null);

    // Konversi data API ({anak: [...]}) ke struktur yang dipakai d3.hierarchy.
    function siapkan(node) {
        node._muat = node.anak.length > 0 || !node.punya_anak;
        node.anak.forEach(siapkan);
        return node;
    }

    const root = d3.hierarchy(siapkan(S.data), (d) => d.anak);
    root.x0 = 0;
    root.y0 = 0;
    // Awal tampil 2 generasi di bawah akar; sisanya dilipat.
    root.descendants().forEach((d) => {
        if (d.depth >= 2 && d.children) {
            d._children = d.children;
            d.children = null;
        }
    });

    let terpilih = root.data.id;
    const tree = d3.tree().nodeSize([JARAK_Y, JARAK_X]);

    function punyaTersembunyi(d) {
        return !!d._children || (!d.data._muat && d.data.punya_anak);
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
            .transition().duration(durasi)
            .attr('d', (d) => diagonal(d.source, d.target));
        link.exit().transition().duration(durasi)
            .attr('d', () => diagonal({x: sumber.x, y: sumber.y}, {x: sumber.x, y: sumber.y}))
            .remove();

        const node = g.selectAll('g.node').data(nodes, (d) => d.data.id);
        const masuk = node.enter().append('g')
            .attr('class', 'node')
            .attr('transform', `translate(${sumber.y0},${sumber.x0})`)
            .on('click', (e, d) => pilih(d));

        masuk.append('rect').attr('class', 'kartu')
            .attr('x', 0).attr('y', -TINGGI / 2).attr('width', LEBAR).attr('height', TINGGI)
            .attr('fill', '#fff')
            .attr('stroke', (d) => warna[d.data.garis] || '#999');
        masuk.append('rect')
            .attr('x', 0).attr('y', -TINGGI / 2).attr('width', 6).attr('height', TINGGI)
            .attr('fill', (d) => warna[d.data.garis] || '#999');
        masuk.append('text').attr('class', 'nama').attr('x', 14).attr('y', -5)
            .text((d) => potong(d.data.nama_lengkap, 24));
        masuk.append('text').attr('class', 'sub').attr('x', 14).attr('y', 12)
            .text((d) => `G${d.data.generasi_ke} · ${d.data.kode_anggota}${d.data.status_hidup === 'meninggal' ? ' · †' : ''}`);

        const tombol = masuk.append('g').attr('class', 'toggle')
            .attr('transform', `translate(${LEBAR},0)`)
            .on('click', (e, d) => {
                e.stopPropagation();
                buka(d);
            });
        tombol.append('circle').attr('r', 9).attr('fill', '#fff').attr('stroke', '#bdb3a8');
        tombol.append('text').attr('text-anchor', 'middle').attr('dy', 4).attr('font-size', 13);

        const semua = masuk.merge(node);
        semua.transition().duration(durasi).attr('transform', (d) => `translate(${d.y},${d.x})`);
        semua.classed('terpilih', (d) => d.data.id === terpilih);
        semua.select('g.toggle')
            .style('display', (d) => (d.children || punyaTersembunyi(d)) ? null : 'none')
            .select('text').text((d) => d.children ? '−' : '+');

        node.exit().transition().duration(durasi)
            .attr('transform', `translate(${sumber.y},${sumber.x})`).remove();

        nodes.forEach((d) => {
            d.x0 = d.x;
            d.y0 = d.y;
        });
    }

    function diagonal(s, t) {
        const sx = s.y + LEBAR, tx = t.y;
        return `M${sx},${s.x} C${(sx + tx) / 2},${s.x} ${(sx + tx) / 2},${t.x} ${tx},${t.x}`;
    }

    async function buka(d) {
        if (d.children) {
            d._children = d.children;
            d.children = null;
        } else if (d._children) {
            d.children = d._children;
            d._children = null;
        } else if (!d.data._muat && d.data.punya_anak) {
            try {
                const res = await fetch(`${S.baseUrl}api/pohon/${d.data.id}?kedalaman=2`);
                const data = siapkan(await res.json());
                d.data.anak = data.anak;
                d.data._muat = true;
                d.children = data.anak.map((a) => {
                    const h = d3.hierarchy(a, (x) => x.anak);
                    h.descendants().forEach((c) => {
                        c.depth += d.depth + 1;
                        if (c.depth - d.depth >= 1 && c.children) {
                            c._children = c.children;
                            c.children = null;
                        }
                    });
                    h.parent = d;
                    return h;
                });
            } catch (err) {
                alert('Gagal memuat data. Periksa koneksi lalu coba lagi.');
                return;
            }
        }
        render(d);
    }

    function pilih(d) {
        terpilih = d.data.id;
        g.selectAll('g.node').classed('terpilih', (n) => n.data.id === terpilih);
        const p = d.data;
        const url = (path) => S.baseUrl + path;
        const hidup = p.status_hidup === 'meninggal' ? 'Meninggal' : (p.status_hidup === 'hidup' ? 'Hidup' : 'Tidak diketahui');
        document.getElementById('panelOrang').innerHTML = `
            <div class="card-body">
                <span class="badge badge-garis garis-${esc(p.garis)} mb-2">${esc(S.label[p.garis] || p.garis)}</span>
                <h2 class="h5 mb-0">${esc(p.nama_lengkap)}</h2>
                ${p.gelar_adat ? `<div class="small text-body-secondary">${esc(p.gelar_adat)}</div>` : ''}
                <dl class="data-profil mt-3 mb-3">
                    <dt>Kode anggota</dt><dd class="font-monospace small">${esc(p.kode_anggota)}</dd>
                    <dt>Generasi</dt><dd>${p.generasi_ke}</dd>
                    <dt>Status</dt><dd>${hidup}</dd>
                </dl>
                <div id="tuturPanel"></div>
                <div class="d-grid gap-2">
                    <a class="btn btn-utama btn-sm" href="${url('anggota/' + p.id)}">
                        ${S.login ? '<i class="bi bi-person-vcard"></i> Lihat profil' : '<i class="bi bi-lock"></i> Masuk untuk lihat profil'}
                    </a>
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
                ? `<div class="tutur mb-3"><div class="ipon-kecil"></div><div class="isi py-2">
                        <div class="label">Anda memanggil</div><div class="sebutan" style="font-size:1.4rem">${esc(t.sebutan)}</div>
                        <div class="ket small">Ia memanggil Anda: <b class="text-white">${esc(t.balik)}</b></div></div></div>`
                : `<p class="small text-teks-2">${esc(t.pesan)}</p>`;
        } catch (e) { /* panel partuturan bersifat tambahan */ }
    }

    function potong(teks, n) {
        return teks.length > n ? teks.slice(0, n - 1) + '…' : teks;
    }

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    }

    function posisiAwal() {
        const box = svg.node().getBoundingClientRect();
        const skala = box.width < 600 ? 0.55 : 0.9;
        svg.transition().duration(300).call(zoom.transform, d3.zoomIdentity.translate(box.width < 600 ? 12 : 40, box.height / 2).scale(skala));
    }

    document.getElementById('zoomMasuk').addEventListener('click', () => svg.transition().call(zoom.scaleBy, 1.25));
    document.getElementById('zoomKeluar').addEventListener('click', () => svg.transition().call(zoom.scaleBy, 0.8));
    document.getElementById('zoomReset').addEventListener('click', posisiAwal);

    // Pencarian: buka orang yang dipilih sebagai pusat pohon.
    const input = document.getElementById('cariPohon');
    const hasil = document.getElementById('hasilCari');
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            hasil.innerHTML = '';
            return;
        }
        timer = setTimeout(async () => {
            const res = await fetch(`${S.baseUrl}api/cari?q=${encodeURIComponent(q)}`);
            const rows = await res.json();
            hasil.innerHTML = rows.length === 0
                ? '<div class="list-group-item small text-body-secondary">Tidak ditemukan.</div>'
                : rows.map((r) => `
                    <a class="list-group-item list-group-item-action small" href="${S.baseUrl}silsilah/${r.id}">
                        <div class="fw-medium">${esc(r.nama_lengkap)} <span class="text-body-secondary">· G${r.generasi_ke}</span></div>
                        <div class="text-body-secondary">${esc(r.kode_anggota)}${r.nama_induk ? ' · anak dari ' + esc(r.nama_induk) : ''}</div>
                    </a>`).join('');
        }, 250);
    });
    document.addEventListener('click', (e) => {
        if (!hasil.contains(e.target) && e.target !== input) hasil.innerHTML = '';
    });

    render(root);
    pilih(root);
    posisiAwal();
})();
