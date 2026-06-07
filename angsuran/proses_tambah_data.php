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

	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
	header('Access-Control-Allow-Headers: Content-Type, Authorization');

	include '../koneksi.php';

	$pinjaman_id = $_POST['pinjaman_id'];
	$tgl_bayar = $_POST['tgl_bayar'];
	$jumlah_bayar = $_POST['jumlah_bayar'];

	$query = "INSERT INTO angsuran (pinjaman_id, tgl_bayar, jumlah_bayar) VALUES ('$pinjaman_id', '$tgl_bayar', '$jumlah_bayar')";

	if (!mysqli_query($koneksi, $query)) {
		$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
		error_log($entry, 3, $log_file);
		echo "Error adding data.";
	} else {
		$totalQuery = "SELECT COALESCE(SUM(jumlah_bayar), 0) as total FROM angsuran WHERE pinjaman_id = '$pinjaman_id'";
		$totalHasil = mysqli_query($koneksi, $totalQuery);
		$totalData = mysqli_fetch_array($totalHasil);
		$totalBayar = $totalData['total'];

		$pinjamanQuery = "SELECT jumlah_pinjam FROM pinjaman WHERE id = '$pinjaman_id'";
		$pinjamanHasil = mysqli_query($koneksi, $pinjamanQuery);
		$pinjamanData = mysqli_fetch_array($pinjamanHasil);
		$jumlahPinjaman = $pinjamanData['jumlah_pinjam'];

		if ($totalBayar >= $jumlahPinjaman) {
			$updateQuery = "UPDATE pinjaman SET status = 'Lunas' WHERE id = '$pinjaman_id'";
			mysqli_query($koneksi, $updateQuery);
		}
	}

?>
