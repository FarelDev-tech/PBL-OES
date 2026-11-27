<?php

class ProkerModel
{
    private PDO $conn;
    private string $table_name = 'proker';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(string $statusFilter = ''): array
    {
        $sql = "WITH rekap_kegiatan AS (
                    SELECT id_proker, COUNT(*) AS total_kegiatan FROM sesi_presensi GROUP BY id_proker
                ),
                rekap_panitia AS (
                    SELECT id_proker, COUNT(*) AS total_panitia FROM panitia_proker GROUP BY id_proker
                )
                SELECT p.*,
                    COALESCE(rk.total_kegiatan, 0) AS total_kegiatan,
                    COALESCE(rp.total_panitia, 0) AS total_panitia
                FROM {$this->table_name} p
                LEFT JOIN rekap_kegiatan rk ON p.id_proker = rk.id_proker
                LEFT JOIN rekap_panitia rp ON p.id_proker = rp.id_proker";
        $params = [];
        if (!empty($statusFilter)) {
            $sql .= " WHERE p.status_proker = :status";
            $params['status'] = $statusFilter;
        }
        $sql .= " ORDER BY CASE WHEN p.status_proker = 'Berjalan' THEN 0 WHEN p.status_proker = 'Perencanaan' THEN 1 ELSE 2 END, p.id_proker DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $idProker): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE id_proker = :id LIMIT 1");
        $stmt->execute(['id' => $idProker]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function create(array $data): int
    {
        $this->conn->beginTransaction();
        try {
            $stmtIns = $this->conn->prepare(
                "INSERT INTO {$this->table_name} (nama_proker, periode, deskripsi, tanggal_mulai, tanggal_selesai, status_proker)
                VALUES (:nama, :periode, :desk, :mulai, :selesai, :status)
                RETURNING id_proker"
            );
            $stmtIns->execute([
                'nama'    => $data['nama_proker'],
                'periode' => $data['periode'] ?? '2026/2027',
                'desk'    => !empty($data['deskripsi']) ? $data['deskripsi'] : null,
                'mulai'   => $data['tanggal_mulai'],
                'selesai' => $data['tanggal_selesai'],
                'status'  => in_array($data['status_proker'] ?? '', ['Perencanaan', 'Berjalan', 'Selesai'], true) ? $data['status_proker'] : 'Berjalan',
            ]);
            $newId = (int) $stmtIns->fetchColumn();

            // Buat 4 divisi standar otomatis
            $stmtDiv = $this->conn->prepare("INSERT INTO divisi (id_proker, nama_divisi) VALUES (:p, :nama)");
            foreach (['Divisi Acara', 'Divisi Humas', 'Divisi Dokumentasi', 'Divisi Perlengkapan'] as $div) {
                $stmtDiv->execute(['p' => $newId, 'nama' => $div]);
            }

            $this->conn->commit();
            return $newId;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $idProker, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table_name}
            SET nama_proker = :nama, periode = :periode, deskripsi = :desk,
                tanggal_mulai = :mulai, tanggal_selesai = :selesai, status_proker = :status
            WHERE id_proker = :id"
        );
        return $stmt->execute([
            'nama'    => $data['nama_proker'],
            'periode' => $data['periode'] ?? '2026/2027',
            'desk'    => !empty($data['deskripsi']) ? $data['deskripsi'] : null,
            'mulai'   => $data['tanggal_mulai'],
            'selesai' => $data['tanggal_selesai'],
            'status'  => $data['status_proker'] ?? 'Berjalan',
            'id'      => $idProker,
        ]);
    }

    public function delete(int $idProker): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id_proker = :id");
        return $stmt->execute(['id' => $idProker]);
    }

    public function getDivisi(int $idProker): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM divisi WHERE id_proker = :id_proker ORDER BY id_divisi ASC");
        $stmt->execute(['id_proker' => $idProker]);
        return $stmt->fetchAll();
    }

    public function addDivisi(int $idProker, string $namaDivisi): bool
    {
        $stmt = $this->conn->prepare("INSERT INTO divisi (id_proker, nama_divisi) VALUES (:p, :nama)");
        return $stmt->execute(['p' => $idProker, 'nama' => $namaDivisi]);
    }

    public function deleteDivisi(int $idProker, int $idDivisi): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM divisi WHERE id_divisi = :id AND id_proker = :p");
        return $stmt->execute(['id' => $idDivisi, 'p' => $idProker]);
    }

    public function getProkerByUser(int $userId, string $statusFilter = ''): array
    {
        $sql = "SELECT p.id_proker, p.nama_proker, p.status_proker, pp.jabatan, pp.id_divisi,
                    COALESCE(d.nama_divisi, 'Umum') AS nama_divisi
                FROM panitia_proker pp
                JOIN proker p ON pp.id_proker = p.id_proker
                LEFT JOIN divisi d ON pp.id_divisi = d.id_divisi
                WHERE pp.id_user = :id_user";
        $params = ['id_user' => $userId];
        if (!empty($statusFilter) && $statusFilter !== 'ALL') {
            $sql .= " AND p.status_proker = :status";
            $params['status'] = $statusFilter;
        } elseif ($statusFilter !== 'ALL') {
            $sql .= " AND p.status_proker != 'Selesai'";
        }
        $sql .= " ORDER BY CASE WHEN p.status_proker = 'Berjalan' THEN 0 ELSE 1 END, p.id_proker DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPanitiaUser(int $userId, int $idProker): ?array
    {
        $sql = "SELECT pp.*, p.nama_proker, d.nama_divisi
                FROM panitia_proker pp
                JOIN proker p ON pp.id_proker = p.id_proker
                LEFT JOIN divisi d ON pp.id_divisi = d.id_divisi
                WHERE pp.id_user = :u AND pp.id_proker = :p
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['u' => $userId, 'p' => $idProker]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getKegiatan(int $idProker): array
    {
        $sql = "SELECT vk.*,
                    COALESCE((SELECT COUNT(*) FROM presensi pr WHERE pr.id_kegiatan = vk.id_kegiatan AND pr.status_validasi = 'Disetujui'), 0) AS total_hadir
                FROM v_kegiatan_lengkap vk
                WHERE vk.id_proker = :id_proker
                ORDER BY vk.created_at DESC, vk.id_kegiatan DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_proker' => $idProker]);
        return $stmt->fetchAll();
    }

    public function createKegiatan(array $data): bool
    {
        $hasJadwal = !empty($data['tanggal']);
        $statusDetail = $hasJadwal ? 'Lengkap' : 'Belum Dilengkapi';

        if ($hasJadwal && !empty($data['id_proker'])) {
            $proker = $this->getById((int) $data['id_proker']);
            if ($proker && !empty($proker['tanggal_mulai']) && !empty($proker['tanggal_selesai'])) {
                if ($data['tanggal'] < $proker['tanggal_mulai'] || $data['tanggal'] > $proker['tanggal_selesai']) {
                    $tglKegiatan = function_exists('formatTanggal') ? formatTanggal($data['tanggal']) : $data['tanggal'];
                    $tglMulai    = function_exists('formatTanggal') ? formatTanggal($proker['tanggal_mulai']) : $proker['tanggal_mulai'];
                    $tglSelesai  = function_exists('formatTanggal') ? formatTanggal($proker['tanggal_selesai']) : $proker['tanggal_selesai'];
                    throw new InvalidArgumentException(
                        "Tanggal kegiatan ({$tglKegiatan}) harus berada dalam batas waktu program kerja ({$tglMulai} s/d {$tglSelesai})."
                    );
                }
            }
        }

        $sql = "INSERT INTO sesi_presensi (
                    id_proker, id_divisi, nama_kegiatan, jenis_kegiatan, bobot_poin,
                    tanggal, jam_mulai, jam_selesai, pelaksanaan, tempat_atau_link,
                    status_detail, status_sesi
                ) VALUES (
                    :id_proker, :id_divisi, :nama_kegiatan, :jenis_kegiatan, :bobot_poin,
                    :tanggal, :jam_mulai, :jam_selesai, :pelaksanaan, :tempat_atau_link,
                    :status_detail, 'Dibuka'
                )";
        $stmt = $this->conn->prepare($sql);
        $pelaksanaan = $data['pelaksanaan'] ?? $data['mode_kegiatan'] ?? null;
        return $stmt->execute([
            'id_proker'        => $data['id_proker'],
            'id_divisi'        => !empty($data['id_divisi']) ? $data['id_divisi'] : null,
            'nama_kegiatan'    => $data['nama_kegiatan'],
            'jenis_kegiatan'   => $data['jenis_kegiatan'],
            'bobot_poin'       => (int) ($data['bobot_poin'] ?? 10),
            'tanggal'          => !empty($data['tanggal']) ? $data['tanggal'] : null,
            'jam_mulai'        => !empty($data['jam_mulai']) ? $data['jam_mulai'] : null,
            'jam_selesai'      => !empty($data['jam_selesai']) ? $data['jam_selesai'] : null,
            'pelaksanaan'      => !empty($pelaksanaan) ? $pelaksanaan : null,
            'tempat_atau_link' => !empty($data['tempat_atau_link']) ? $data['tempat_atau_link'] : null,
            'status_detail'    => $statusDetail,
        ]);
    }

    public function deleteKegiatan(int $idKegiatan): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM sesi_presensi WHERE id_kegiatan = :id");
        return $stmt->execute(['id' => $idKegiatan]);
    }

    public function getDetailKegiatan(int $idKegiatan): ?array
    {
        $stmt = $this->conn->prepare("SELECT k.*, p.nama_proker, d.nama_divisi
                                    FROM sesi_presensi k
                                    JOIN proker p ON k.id_proker = p.id_proker
                                    LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                                    WHERE k.id_kegiatan = :id LIMIT 1");
        $stmt->execute(['id' => $idKegiatan]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function updateDetailKegiatan(int $idKegiatan, array $data): bool
    {
        if (!empty($data['tanggal'])) {
            $stmtP = $this->conn->prepare(
                "SELECT p.tanggal_mulai, p.tanggal_selesai 
                 FROM sesi_presensi s 
                 JOIN proker p ON s.id_proker = p.id_proker 
                 WHERE s.id_kegiatan = :id LIMIT 1"
            );
            $stmtP->execute(['id' => $idKegiatan]);
            $proker = $stmtP->fetch();
            if ($proker && !empty($proker['tanggal_mulai']) && !empty($proker['tanggal_selesai'])) {
                if ($data['tanggal'] < $proker['tanggal_mulai'] || $data['tanggal'] > $proker['tanggal_selesai']) {
                    $tglKegiatan = function_exists('formatTanggal') ? formatTanggal($data['tanggal']) : $data['tanggal'];
                    $tglMulai    = function_exists('formatTanggal') ? formatTanggal($proker['tanggal_mulai']) : $proker['tanggal_mulai'];
                    $tglSelesai  = function_exists('formatTanggal') ? formatTanggal($proker['tanggal_selesai']) : $proker['tanggal_selesai'];
                    throw new InvalidArgumentException(
                        "Tanggal kegiatan ({$tglKegiatan}) harus berada dalam batas waktu program kerja ({$tglMulai} s/d {$tglSelesai})."
                    );
                }
            }
        }

        $sql = "UPDATE sesi_presensi
                SET tanggal = :tanggal,
                    jam_mulai = :jam_mulai,
                    jam_selesai = :jam_selesai,
                    pelaksanaan = :pelaksanaan,
                    tempat_atau_link = :tempat_atau_link,
                    status_detail = 'Lengkap'
                WHERE id_kegiatan = :id";
        $stmt = $this->conn->prepare($sql);
        $pelaksanaan = $data['pelaksanaan'] ?? $data['mode_kegiatan'] ?? 'Offline';
        return $stmt->execute([
            'tanggal'          => $data['tanggal'],
            'jam_mulai'        => $data['jam_mulai'],
            'jam_selesai'      => $data['jam_selesai'],
            'pelaksanaan'      => $pelaksanaan,
            'tempat_atau_link' => $data['tempat_atau_link'],
            'id'               => $idKegiatan,
        ]);
    }

    public function getPanitia(int $idProker): array
    {
        $sql = "SELECT pp.*, u.nim, u.nama, u.email, d.nama_divisi
                FROM panitia_proker pp
                JOIN users u ON pp.id_user = u.id_user
                LEFT JOIN divisi d ON pp.id_divisi = d.id_divisi
                WHERE pp.id_proker = :id_proker
                ORDER BY CASE WHEN pp.jabatan = 'Koordinator' THEN 0 ELSE 1 END, d.id_divisi ASC NULLS LAST, u.nama ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_proker' => $idProker]);
        return $stmt->fetchAll();
    }

    public function getUserBelumPanitia(int $idProker): array
    {
        $sql = "SELECT u.id_user, u.nim, u.nama, u.email
                FROM users u
                WHERE u.status_akun = 'Aktif'
                    AND u.role NOT IN ('admin', 'super_admin')
                    AND u.id_user NOT IN (SELECT pp.id_user FROM panitia_proker pp WHERE pp.id_proker = :id_proker)
                ORDER BY u.nama ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_proker' => $idProker]);
        return $stmt->fetchAll();
    }

    public function addBatchPanitia(int $idProker, array $userIds, ?int $idDivisi, string $jabatan): int
    {
        if (empty($userIds)) {
            return 0;
        }
        $sukses = 0;
        $stmt = $this->conn->prepare("INSERT INTO panitia_proker (id_proker, id_user, id_divisi, jabatan) VALUES (:p, :u, :d, :j)");
        foreach ($userIds as $uid) {
            try {
                $ok = $stmt->execute([
                    'p' => $idProker,
                    'u' => (int) $uid,
                    'd' => $idDivisi ?: null,
                    'j' => $jabatan,
                ]);
                if ($ok) {
                    $sukses++;
                }
            } catch (Throwable $e) {
                // Lewati duplikasi
            }
        }
        return $sukses;
    }

    public function deletePanitia(int $idPanitia): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM panitia_proker WHERE id_panitia = :id");
        return $stmt->execute(['id' => $idPanitia]);
    }

    public function updateJabatanPanitia(int $idPanitia, ?int $idDivisi, string $jabatan): bool
    {
        $stmt = $this->conn->prepare("UPDATE panitia_proker SET id_divisi = :d, jabatan = :j WHERE id_panitia = :id");
        return $stmt->execute([
            'd'  => $idDivisi ?: null,
            'j'  => $jabatan,
            'id' => $idPanitia,
        ]);
    }

    public function getIdUserByPanitia(int $idPanitia): int
    {
        $stmt = $this->conn->prepare("SELECT id_user FROM panitia_proker WHERE id_panitia = :id LIMIT 1");
        $stmt->execute(['id' => $idPanitia]);
        return (int) $stmt->fetchColumn();
    }

    public function setKoordinatorDivisi(int $idProker, int $idDivisi, int $idUserKoor): void
    {
        $stmtReset = $this->conn->prepare(
            "UPDATE panitia_proker SET jabatan = 'Anggota'
            WHERE id_proker = :p AND id_divisi = :d AND jabatan = 'Koordinator'"
        );
        $stmtReset->execute(['p' => $idProker, 'd' => $idDivisi]);

        if ($idUserKoor > 0) {
            $stmtCek = $this->conn->prepare("SELECT id_panitia FROM panitia_proker WHERE id_proker = :p AND id_user = :u");
            $stmtCek->execute(['p' => $idProker, 'u' => $idUserKoor]);
            $idPanitiaAda = (int) $stmtCek->fetchColumn();

            if ($idPanitiaAda > 0) {
                $this->updateJabatanPanitia($idPanitiaAda, $idDivisi, 'Koordinator');
            } else {
                $this->addBatchPanitia($idProker, [$idUserKoor], $idDivisi, 'Koordinator');
            }
        }
    }

    public function syncUserRole(int $userId): string
    {
        $stmtUser = $this->conn->prepare("SELECT role FROM users WHERE id_user = :u LIMIT 1");
        $stmtUser->execute(['u' => $userId]);
        return (string) $stmtUser->fetchColumn();
    }
}

