// Menyalin berkas dist library frontend ke public/assets/vendor.
import { cpSync, mkdirSync, rmSync } from 'node:fs';

const tujuan = 'public/assets/vendor';
rmSync(tujuan, { recursive: true, force: true });

const salin = [
    ['node_modules/bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
    ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap/bootstrap.bundle.min.js'],
    ['node_modules/bootstrap-icons/font/bootstrap-icons.min.css', 'bootstrap-icons/bootstrap-icons.min.css'],
    ['node_modules/bootstrap-icons/font/fonts', 'bootstrap-icons/fonts'],
    ['node_modules/d3/dist/d3.min.js', 'd3/d3.min.js'],
];

for (const [dari, ke] of salin) {
    mkdirSync(`${tujuan}/${ke}`.replace(/\/[^/]+$/, ''), { recursive: true });
    cpSync(dari, `${tujuan}/${ke}`, { recursive: true });
}
console.log('Aset disalin ke', tujuan);
