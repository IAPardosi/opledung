<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MargaModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url', 'auth', 'silsilah'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    protected function user(): ?User
    {
        return auth()->loggedIn() ? auth()->user() : null;
    }

    /**
     * Marga yang sedang ditampilkan: ?marga=KODE, marga akun yang login, atau marga aktif pertama.
     *
     * @return array<string, mixed>
     */
    protected function margaAktif(): array
    {
        $model = new MargaModel();
        $kode  = $this->request->getGet('marga');

        $marga = match (true) {
            is_string($kode) && $kode !== ''    => $model->where('kode', strtoupper($kode))->where('is_active', 1)->first(),
            $this->user()?->marga_id !== null   => $model->find($this->user()->marga_id),
            default                             => null,
        };
        $marga ??= $model->where('is_active', 1)->orderBy('id', 'ASC')->first();

        if ($marga === null) {
            throw PageNotFoundException::forPageNotFound('Belum ada marga yang aktif.');
        }

        return $marga;
    }
}
