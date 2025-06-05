<?php

// Mengatur header untuk memberitahu klien bahwa respons adalah JSON
header('Content-Type: application/json');

// Memuat konfigurasi utama dan pustaka yang dibutuhkan
require '../Panel/mainconfig.php';
// require '../lib/check_session.php'; // TIDAK DIPERLUKAN lagi untuk API KEY
// require '../lib/is_login.php';      // TIDAK DIPERLUKAN lagi untuk API KEY

date_default_timezone_set('Asia/Jakarta');

// Inisialisasi variabel untuk menyimpan respons API
$response = [
    'status' => 'error',
    'message' => 'Terjadi kesalahan tidak dikenal.',
    'data' => null
];

// --- LOGIKA AUTENTIKASI API KEY BARU ---
$api_key = null;

// Coba ambil API Key dari HTTP Header "X-API-Key" (disarankan)
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $api_key = $_SERVER['HTTP_X_API_KEY'];
} 
// Atau, coba ambil API Key dari parameter GET 'api_key' (kurang disarankan untuk produksi)
// else if (isset($_GET['api_key'])) {
//     $api_key = $_GET['api_key'];
// }
// Atau, coba ambil API Key dari input POST 'api_key'
else if (isset($_POST['api_key'])) {
    $api_key = $_POST['api_key'];
} else {
    // Jika tidak ada API Key ditemukan di mana pun
    http_response_code(401); // Unauthorized
    $response['message'] = 'API Key tidak ditemukan. Harap sertakan API Key di header X-API-Key atau parameter POST/GET.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Validasi API Key dari database
$login = null; // Inisialisasi variabel login
if ($api_key) {
    // Sanitasi API Key
    $safe_api_key = mysqli_real_escape_string($db, $api_key);
    
    // Query untuk mencari API Key dan mendapatkan data user terkait
    // Asumsi tabel 'users' memiliki kolom 'api_key' atau ada tabel 'api_keys' terpisah
    // Untuk contoh ini, kita asumsikan kolom 'api_key' ada di tabel 'users'
    $user_data = $model->db_query($db, "id, balance", "users", "api_key = '" . $safe_api_key . "' AND status = '1'");

    if ($user_data['count'] > 0) {
        $login = $user_data['rows']; // Data user yang terotentikasi
    }
}

// Periksa apakah API Key valid dan user ditemukan
if (!isset($login) || empty($login['id'])) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'API Key tidak valid atau pengguna tidak ditemukan.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
// --- AKHIR LOGIKA AUTENTIKASI API KEY BARU ---


// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Metode request tidak diizinkan. Gunakan POST.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Mengambil data dari input JSON jika Content-Type adalah application/json
$input = json_decode(file_get_contents('php://input'), true);

// Jika input JSON tidak ada atau tidak valid, kembali ke $_POST
if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    $input = $_POST;
}

// Logic dari kode asli Anda, diadaptasi untuk API
try {
    // Memuat data mod_data (mungkin konfigurasi atau template untuk token/cheat)
    $mod = $model->db_query($db, "*", "mod_data", "id = '1' AND status = '1'");
    // Handle jika mod_data tidak ditemukan
    if ($mod['count'] == 0) {
        http_response_code(500);
        $response['message'] = 'Konfigurasi modul tidak ditemukan atau tidak aktif.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }


    // Daftar input yang diharapkan dari klien
    $input_data_keys = array('service', 'data', 'quantity');
    // Asumsi 'category' juga diharapkan dari input
    $input_data_keys[] = 'category'; 

    // Validasi input dasar: Periksa apakah semua kunci input yang diharapkan ada
    foreach ($input_data_keys as $key) {
        // 'data' bisa kosong jika deskripsi layanan mengizinkan
        if (!isset($input[$key]) || ($input[$key] === '' && $key !== 'data')) { 
            http_response_code(400); // Bad Request
            $response['message'] = 'Input tidak lengkap: `' . $key . '` diperlukan.';
            echo json_encode($response, JSON_PRETTY_PRINT);
            exit();
        }
    }

    // Sanitasi input
    $service_id = mysqli_real_escape_string($db, $input['service']);
    $order_data = mysqli_real_escape_string($db, $input['data']);
    $quantity = (int)$input['quantity']; // Pastikan quantity adalah integer
    $category_id = (int)($input['category'] ?? 0); // Pastikan category adalah integer

    // Validasi input kosong (tambahan)
    if (empty($service_id) || $quantity <= 0) { // $order_data bisa kosong jadi tidak masuk sini
        http_response_code(400); // Bad Request
        $response['message'] = 'Input layanan atau kuantitas tidak boleh kosong atau kuantitas harus lebih dari 0.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }

    // Mengambil detail layanan dari database
    $service = $model->db_query($db, "*", "services", "id = '" . $service_id . "' AND status = '1'");

    // Logika validasi layanan
    if ($service['count'] == 0) {
        http_response_code(404); // Not Found
        $response['message'] = 'Layanan tidak ditemukan atau tidak aktif.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }

    // Hitung total harga dan profit
    $total_price = ($service['rows']['price']) * $quantity;
    $total_profit = ($service['rows']['profit']) * $quantity;

    // Mengambil data provider
    $provider = $model->db_query($db, "*", "provider", "id = '" . $service['rows']['provider_id'] . "'");

    // Validasi provider
    if ($provider['count'] == 0) {
        http_response_code(503); // Service Unavailable
        $response['message'] = 'Maaf, penyedia layanan saat ini tidak tersedia. Silakan kontak Admin.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }

    // Validasi kuantitas minimal
    if ($quantity < $service['rows']['min']) {
        http_response_code(400); // Bad Request
        $response['message'] = 'Jumlah minimal pesan ' . number_format($service['rows']['min'], 0, ',', '.') . '.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }

    // Validasi kuantitas maksimal
    if ($quantity > $service['rows']['max']) {
        http_response_code(400); // Bad Request
        $response['message'] = 'Jumlah maksimal pesan ' . number_format($service['rows']['max'], 0, ',', '.') . '.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }

    // Validasi saldo pengguna
    if ($login['balance'] < $total_price) {
        http_response_code(402); // Payment Required
        $response['message'] = 'Saldo Anda tidak cukup untuk membuat pesanan. Sisa saldo Rp ' . number_format($login['balance'], 0, ',', '.') . '.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }

    // Integrasi API pihak ketiga (placeholder)
    $result_api = true; // Asumsi berhasil jika tidak ada integrasi eksternal
    if ($result_api == false) {
        http_response_code(500); // Internal Server Error (karena ada masalah dengan API eksternal)
        $response['message'] = 'Maaf, terjadi masalah saat memproses pesanan ke penyedia. Silakan kontak Admin.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    } else {
        // Fungsi untuk membuat password/token acak
        function randomPassword() {
            $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
            $pass = array();
            $alphaLength = strlen($alphabet) - 1;
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            return implode($pass);
        }
        $str2 = randomPassword();
        $str1 = "ZONA"; // Awalan kunci

        // Menghitung tanggal kedaluwarsa token/cheat
        // Asumsi $service['rows']['profit'] adalah jumlah hari durasi
        $besok = date('Y-m-d H:i:s', strtotime("+" . $service['rows']['profit'] . " day"));
        $start = date('Y-m-d H:i:s');

        // Menentukan kategori game berdasarkan ID
        $category_name = 'UNKNOW';
        switch ($category_id) {
            case 1: $category_name = 'CODM'; break;
            case 2: $category_name = 'PUBG'; break; 
            case 3: $category_name = 'MLBB'; break;
        }

        // Data pesanan untuk disimpan dan dikembalikan
        $input_post = array(
            'user_id' => $login['id'],
            'service_name' => $service['rows']['service_name'],
            'data' => $order_data,
            'quantity' => $quantity,
            'price' => $total_price,
            'profit' => $total_profit,
            'remains' => $quantity,
            'status' => 'Success', // Atau 'Pending' jika ada proses lanjutan ke provider eksternal
            'token' => $str1 . "-KEY-" . strtoupper($str2),
            'provider_id' => $service['rows']['provider_id'],
            'provider_order_id' => "", // Akan diisi jika terintegrasi dengan API provider
            'created_at' => date('Y-m-d H:i:s')
        );

        // Memulai transaksi database untuk memastikan konsistensi data
        $db->begin_transaction();

        try {
            // Memasukkan data pesanan ke tabel 'orders'
            $insert_order_id = $model->db_insert($db, "orders", $input_post);

            if ($insert_order_id == true) {
                // Mengurangi saldo pengguna
                $update_balance = $model->db_update($db, "users", array('balance' => $login['balance'] - $total_price), "id = '" . $login['id'] . "'");

                // Mencatat log pengurangan saldo
                $insert_balance_log = $model->db_insert($db, "balance_logs", array(
                    'user_id' => $login['id'],
                    'type' => 'minus',
                    'amount' => $total_price,
                    'note' => 'Membuat Pesanan. ID Pesanan: ' . $insert_order_id . '.',
                    'created_at' => date('Y-m-d H:i:s')
                ));

                // Memasukkan data token/cheat
                $insert_token = $model->db_insert($db, "token", array(
                    'uid' => $login['id'],
                    'data' => $order_data,
                    'access_key' => $str1 . "-KEY-" . strtoupper($str2),
                    'cheat_name' => $mod['rows']['name'],
                    'cheat_text' => $mod['rows']['text'],
                    'cheat_exp' => $besok,
                    'cheat_slot' => $quantity, 
                    'serial' => 0, 
                    'Status' => $mod['rows']['mod_status'],
                    'vip' => 1, 
                    'games' => $category_name,
                    'durasi' => $service['rows']['profit'], 
                    'start' => $start
                ));

                // Periksa apakah semua operasi database berhasil
                if ($update_balance && $insert_balance_log && $insert_token) {
                    $db->commit(); // Komit transaksi jika semua berhasil
                    http_response_code(200); // OK
                    $response['status'] = 'success';
                    $response['message'] = 'Pesanan berhasil dibuat.';
                    $response['data'] = $input_post; // Mengembalikan data pesanan yang dibuat
                    $response['data']['order_id'] = $insert_order_id;
                    $response['data']['total_price_formatted'] = 'Rp ' . number_format($total_price, 0, ',', '.');
                    $response['data']['current_balance'] = $login['balance'] - $total_price;

                } else {
                    $db->rollback(); // Rollback jika ada yang gagal
                    http_response_code(500);
                    $response['message'] = 'Terjadi kesalahan saat menyimpan data pesanan atau mengupdate saldo.';
                }
            } else {
                $db->rollback(); // Rollback jika insert order gagal
                http_response_code(500);
                $response['message'] = 'Pesanan gagal dibuat karena masalah database.';
            }
        } catch (Exception $e) {
            $db->rollback(); // Rollback jika ada exception
            http_response_code(500);
            $response['message'] = 'Terjadi kesalahan sistem internal saat memproses pesanan: ' . $e->getMessage();
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Terjadi kesalahan tak terduga dalam logika API: ' . $e->getMessage();
}

// Mengembalikan respons JSON
echo json_encode($response, JSON_PRETTY_PRINT);
exit(); // Penting untuk menghentikan eksekusi script setelah mengirim respons JSON

?>