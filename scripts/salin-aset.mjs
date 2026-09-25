// Menyalin berkas dist library frontend dan font ke public/assets/vendor.
import { cpSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';

const tujuan = 'public/assets/vendor';
rmSync(tujuan, { recursive: true, force: true });

const salin = [
    ['node_modules/bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
    ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap/bootstrap.bundle.min.js'],
    ['node_modules/bootstrap-icons/font/bootstrap-icons.min.css', 'bootstrap-icons/bootstrap-icons.min.css'],
    ['node_modules/bootstrap-icons/font/fonts', 'bootstrap-icons/fonts'],
    ['node_modules/d3/dist/d3.min.js', 'd3/d3.min.js'],
];

// Font: hanya subset latin woff2 (cukup untuk Bahasa Indonesia dan Batak).
const font = [
    ['Playfair Display', 'playfair-display', [700, 800]],
    ['Plus Jakarta Sans', 'plus-jakarta-sans', [400, 500, 600, 700]],
];
let css = '/* Dibuat oleh scripts/salin-aset.mjs */\n';
for (const [nama, paket, bobot] of font) {
    for (const b of bobot) {
        const berkas = `${paket}-latin-${b}-normal.woff2`;
        salin.push([`node_modules/@fontsource/${paket}/files/${berkas}`, `fonts/${berkas}`]);
        css += `@font-face{font-family:'${nama}';font-style:normal;font-display:swap;font-weight:${b};src:url(./${berkas}) format('woff2');}\n`;
    }
}

for (const [dari, ke] of salin) {
    mkdirSync(`${tujuan}/${ke}`.replace(/\/[^/]+$/, ''), { recursive: true });
    cpSync(dari, `${tujuan}/${ke}`, { recursive: true });
}
writeFileSync(`${tujuan}/fonts/fonts.css`, css);
console.log('Aset disalin ke', tujuan);
