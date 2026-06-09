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

$bulan = $_GET['bulan']; // format: YYYY-MM

// Summary
$summaryQuery = "SELECT 
  COALESCE(SUM(s.jumlah), 0) AS total_simpanan,
  COALESCE(SUM(CASE WHEN p.status = 'Belum Lunas' THEN p.jumlah_pinjam ELSE 0 END), 0) AS total_pinjaman_aktif,
  COALESCE(SUM(CASE WHEN p.status = 'Belum Lunas' THEN p.jumlah_pinjam - COALESCE(a.total_angsuran, 0) ELSE 0 END), 0) AS total_sisa_pinjaman
  FROM simpanan s
  LEFT JOIN pinjaman p ON DATE_FORMAT(p.tgl_pinjam, '%Y-%m') = '$bulan'
  LEFT JOIN (SELECT pinjaman_id, SUM(jumlah_bayar) AS total_angsuran FROM angsuran GROUP BY pinjaman_id) a ON p.id = a.pinjaman_id
  WHERE s.tahun_iuran = YEAR('$bulan-01') AND s.bulan_iuran = MONTH('$bulan-01')";
$summaryHasil = mysqli_query($koneksi, $summaryQuery);

if (!$summaryHasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $summaryQuery . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$summaryData = mysqli_fetch_array($summaryHasil);

// Simpanan - pivot per anggota
$simpananQuery = "SELECT s.anggota_id, a.no_urut, a.nama, s.jenis, s.jumlah
  FROM simpanan s
  LEFT JOIN anggota a ON s.anggota_id = a.id
  WHERE s.tahun_iuran = YEAR('$bulan-01') AND s.bulan_iuran = MONTH('$bulan-01')
  ORDER BY a.nama ASC";
$simpananHasil = mysqli_query($koneksi, $simpananQuery);

if (!$simpananHasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $simpananQuery . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$pivoted = [];
while($data = mysqli_fetch_array($simpananHasil)){
	$anggota_id = $data['anggota_id'];
	$jenis = $data['jenis'];
	$jumlah = $data['jumlah'];
	
	if (!isset($pivoted[$anggota_id])) {
		$pivoted[$anggota_id] = [
			'anggota_id' => $anggota_id,
			'no_urut' => $data['no_urut'],
			'nama' => $data['nama'],
			'pokok' => 0,
			'wajib' => 0,
			'investasi' => 0
		];
	}
	
	$lowerJenis = strtolower($jenis);
	if (strpos($lowerJenis, 'pokok') !== false) {
		$pivoted[$anggota_id]['pokok'] += $jumlah;
	} elseif (strpos($lowerJenis, 'wajib') !== false) {
		$pivoted[$anggota_id]['wajib'] += $jumlah;
	} else {
		$pivoted[$anggota_id]['investasi'] += $jumlah;
	}
}

$simpananResult = array_values($pivoted);

// Pinjaman
$pinjamanQuery = "SELECT p.id, p.anggota_id, a.no_urut, a.nama, p.jumlah_pinjam, 
  COALESCE(ag.total_angsuran, 0) AS total_angsuran,
  p.jumlah_pinjam - COALESCE(ag.total_angsuran, 0) AS sisa_pinjaman,
  p.status
  FROM pinjaman p
  LEFT JOIN anggota a ON p.anggota_id = a.id
  LEFT JOIN (SELECT pinjaman_id, SUM(jumlah_bayar) AS total_angsuran FROM angsuran GROUP BY pinjaman_id) ag ON p.id = ag.pinjaman_id
  WHERE DATE_FORMAT(p.tgl_pinjam, '%Y-%m') = '$bulan'
  ORDER BY a.nama ASC";
$pinjamanHasil = mysqli_query($koneksi, $pinjamanQuery);

if (!$pinjamanHasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $pinjamanQuery . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$pinjamanTemp = [];
while($data = mysqli_fetch_array($pinjamanHasil)){
	$pinjamanTemp[] = $data;
}

$result = [
	'summary' => [
		'total_simpanan' => $summaryData['total_simpanan'],
		'total_pinjaman_aktif' => $summaryData['total_pinjaman_aktif'],
		'total_sisa_pinjaman' => $summaryData['total_sisa_pinjaman']
	],
	'simpanan' => $simpananResult,
	'pinjaman' => $pinjamanTemp
];

echo json_encode($result);

?>
