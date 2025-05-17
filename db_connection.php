<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evalu_app";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// ثوابت التشفير
define('ENCRYPTION_KEY', 'hanadimohammedibrahimyousifhhhhh'); // مفتاح تشفير 32 حرفًا
define('CIPHER_METHOD', 'AES-256-CBC'); // طريقة التشفير
?>
<?php
// ... (الكود السابق)

/**
 * تشفير النص باستخدام AES-256
 */
function encryptData($data) {
    $iv_length = openssl_cipher_iv_length(CIPHER_METHOD);
    $iv = openssl_random_pseudo_bytes($iv_length); // إنشاء متجه ابتدائي (IV)
    $encrypted = openssl_encrypt($data, CIPHER_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted); // إرجاع النص المشفر مع IV
}

/**
 * فك تشفير النص باستخدام AES-256
 */
function decryptData($data) {
    $data = base64_decode($data);
    $iv_length = openssl_cipher_iv_length(CIPHER_METHOD);
    $iv = substr($data, 0, $iv_length); // استخراج IV
    $encrypted = substr($data, $iv_length); // استخراج النص المشفر
    return openssl_decrypt($encrypted, CIPHER_METHOD, ENCRYPTION_KEY, 0, $iv);
}
?>