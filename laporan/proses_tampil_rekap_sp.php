<?php
	$log_dir = __DIR__ . '/logs';
	$log_file = $log_dir . '/app.log';

	if (!is_dir($log_dir)) {
		mkdir($log_dir, 0777, true);
	}

	set_error_handler(function ($severity, $message, $file, $line) use ($log_file) {
		$entry = date('c') . " | ERROR | {$message} | {$file}:{$line}\n";
		error_log($entry, 3, $log_file);
		return true;
	});

	set_exception_handler(function ($exception) use ($log_file) {
		$entry = date('c') . " | EXCEPTION | {$exception->getMessage()} | {$exception->getFile()}:{$exception->getLine()}\n";
		error_log($entry, 3, $log_file);
	});

	register_shutdown_function(function () use ($log_file) {
		$error = error_get_last();
		if ($error) {
			$entry = date('c') . " | FATAL | {$error['message']} | {$error['file']}:{$error['line']}\n";
			error_log($entry, 3, $log_file);
		}
	});

	include '../koneksi.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$query = "SELECT 
  a.id,
  a.no_urut,
  a.nama,
  COALESCE(SUM(CASE WHEN LOWER(s.jenis) = 'pokok' THEN s.jumlah ELSE 0 END), 0) AS simpanan_pokok,
  COALESCE(SUM(CASE WHEN LOWER(s.jenis) = 'wajib' THEN s.jumlah ELSE 0 END), 0) AS simpanan_wajib,
  COALESCE(SUM(s.jumlah), 0) AS total_simpanan,
  COALESCE(p.total_pinjaman, 0) AS total_pinjaman,
  COALESCE(ag.total_angsuran, 0) AS total_angsuran,
  COALESCE(p.total_pinjaman, 0) - COALESCE(ag.total_angsuran, 0) AS sisa_pinjaman
  FROM anggota a
  LEFT JOIN simpanan s ON a.id = s.anggota_id
  LEFT JOIN (
    SELECT anggota_id, SUM(jumlah_pinjam) AS total_pinjaman
    FROM pinjaman
    GROUP BY anggota_id
  ) p ON a.id = p.anggota_id
  LEFT JOIN (
    SELECT p2.anggota_id, SUM(ag2.jumlah_bayar) AS total_angsuran
    FROM angsuran ag2
    JOIN pinjaman p2 ON ag2.pinjaman_id = p2.id
    GROUP BY p2.anggota_id
  ) ag ON a.id = ag.anggota_id
  GROUP BY a.id, a.no_urut, a.nama
  ORDER BY a.nama ASC";

$hasil = mysqli_query($koneksi, $query);

if (!$hasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$result = [];
while($data = mysqli_fetch_array($hasil)){
	$result[] = [
		'id' => $data['id'],
		'no_urut' => $data['no_urut'],
		'nama' => $data['nama'],
		'simpanan_pokok' => (float)$data['simpanan_pokok'],
		'simpanan_wajib' => (float)$data['simpanan_wajib'],
		'total_simpanan' => (float)$data['total_simpanan'],
		'total_pinjaman' => (float)$data['total_pinjaman'],
		'total_angsuran' => (float)$data['total_angsuran'],
		'sisa_pinjaman' => (float)$data['sisa_pinjaman']
	];
}

echo json_encode($result);

?>
