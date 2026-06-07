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
  COALESCE(SUM(CASE WHEN tipe = 'Pemasukan' THEN jumlah ELSE 0 END), 0) AS total_masuk,
  COALESCE(SUM(CASE WHEN tipe = 'Pengeluaran' THEN jumlah ELSE 0 END), 0) AS total_keluar
  FROM transaksi_kas 
  WHERE DATE_FORMAT(tgl_transaksi, '%Y-%m') = '$bulan'";
$summaryHasil = mysqli_query($koneksi, $summaryQuery);

if (!$summaryHasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $summaryQuery . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$summaryData = mysqli_fetch_array($summaryHasil);
$totalMasuk = $summaryData['total_masuk'];
$totalKeluar = $summaryData['total_keluar'];
$saldo = $totalMasuk - $totalKeluar;

// Transaksi list
$transaksiQuery = "SELECT id, tgl_transaksi, tipe, jumlah, keterangan 
  FROM transaksi_kas 
  WHERE DATE_FORMAT(tgl_transaksi, '%Y-%m') = '$bulan' 
  ORDER BY tgl_transaksi DESC, id DESC";
$transaksiHasil = mysqli_query($koneksi, $transaksiQuery);

if (!$transaksiHasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $transaksiQuery . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$transaksiTemp = [];
while($data = mysqli_fetch_array($transaksiHasil)){
	$transaksiTemp[] = $data;
}

$result = [
	'summary' => [
		'total_masuk' => $totalMasuk,
		'total_keluar' => $totalKeluar,
		'saldo' => $saldo
	],
	'transaksi' => $transaksiTemp
];

echo json_encode($result);

?>
