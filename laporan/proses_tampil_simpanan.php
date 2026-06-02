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

$query = "SELECT anggota_id, jenis, SUM(jumlah) AS total_simpanan FROM simpanan GROUP BY anggota_id, jenis ORDER BY anggota_id DESC";
$hasil = mysqli_query($koneksi, $query);
if (!$hasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

// Pivot data: one row per anggota_id, each jenis becomes a field
$pivoted = [];

while($data = mysqli_fetch_array($hasil)){
	$anggota_id = $data['anggota_id'];
	$jenis = $data['jenis'];
	$total = $data['total_simpanan'];
	
	if (!isset($pivoted[$anggota_id])) {
		$pivoted[$anggota_id] = ['anggota_id' => $anggota_id];
	}
	
	$pivoted[$anggota_id][$jenis] = $total;
}

// Convert to indexed array and sort by anggota_id descending
$temp = array_values($pivoted);
usort($temp, function($a, $b) {
	return $b['anggota_id'] - $a['anggota_id'];
});

echo json_encode($temp);

?>