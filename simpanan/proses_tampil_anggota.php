<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include '../koneksi.php'; 

$query = "SELECT * FROM anggota ORDER BY no_anggota ASC";
$hasil = mysqli_query($koneksi, $query);

$temp = [];
while ($data = mysqli_fetch_array($hasil)) {
    $temp[] = $data;
}

echo json_encode($temp);
?>