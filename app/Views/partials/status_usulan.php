<?= [
    'pending'   => '<span class="badge text-bg-warning">Menunggu</span>',
    'disetujui' => '<span class="badge text-bg-success">Disetujui</span>',
    'ditolak'   => '<span class="badge text-bg-danger">Ditolak</span>',
][$status] ?? esc($status) ?>
