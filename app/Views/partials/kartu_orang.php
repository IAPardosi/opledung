<?php
/**
 * Kartu kecil seseorang untuk tampilan keluarga dekat.
 *
 * @var \App\Entities\Person|array<string, mixed> $o
 * @var array<int, ?string>                        $tutur
 */
$r     = $o instanceof \App\Entities\Person ? $o->toRawArray() : $o;
$saya  = ($sayaId ?? null) === (int) $r['id'];
$pusat = ($pusat ?? false);
$link  = $r['garis'] === 'pasangan' ? site_url('anggota/' . $r['id']) : site_url('keluarga-dekat/' . $r['id']);
?>
<a href="<?= $link ?>" class="kartu-orang<?= $pusat ? ' saya' : '' ?>">
    <span class="avatar avatar-sm garis-<?= esc($r['garis'], 'attr') ?>"><?= esc(inisial($r['nama_lengkap'])) ?></span>
    <span class="d-block" style="min-width:0">
        <span class="n d-block text-truncate"><?= esc($r['nama_lengkap']) ?></span>
        <span class="k d-block"><?= esc(trim(implode(' · ', array_filter([
            $saya ? 'Anda' : ($tutur[(int) $r['id']] ?? null),
            $ket ?? null,
            lahir_wafat($r),
        ])))) ?: 'Sundut ' . (int) $r['generasi_ke'] ?></span>
    </span>
</a>
