<?php

use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;
use Yshabanei\BugTracker\Helpers\Config;

require_once './vendor/autoload.php';

header('Content-Type: application/json');

/**
 * ثبت یک باگ در دیتابیس
 *
 * @param array $data آرایه شامل name, user, email, link
 * @return array پاسخ JSON شامل اطلاعات رکورد ثبت شده یا خطا
 */
function createBug(array $data): array
{
    try {
        $dbConfig = Config::get('database', 'pdo_testing');
        $pdoConnection = new PDODatabaseConnection($dbConfig);
        $pdo = $pdoConnection->connect();
        $queryBuilder = new PDOQueryBuilder($pdo);
    } catch (ConfigFileNotFoundException | ConfigNotValidException | DatabaseConnectionException $e) {
        return ['error' => "خطا در اتصال به دیتابیس: " . $e->getMessage()];
    }

    // بررسی فیلدهای لازم
    $required = ['name', 'user', 'email', 'link'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            return ['error' => "فیلد {$field} فرستاده نشده است"];
        }
    }

    // ایجاد رکورد جدید
    try {
        $id = $queryBuilder->table('bugs')->create([
            'name' => $data['name'],
            'user' => $data['user'],
            'email' => $data['email'],
            'link' => $data['link'],
        ]);

        return [
            'id' => $id,
            'name' => $data['name'],
            'user' => $data['user'],
            'email' => $data['email'],
            'link' => $data['link']
        ];
    } catch (\Exception $e) {
        return ['error' => 'خطا در ایجاد رکورد: ' . $e->getMessage()];
    }
}

// دریافت داده‌های JSON
$inputData = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$inputData) {
        echo json_encode(['error' => 'داده‌های JSON معتبر نیست یا ارسال نشده‌اند']);
        exit;
    }

    $response = createBug($inputData);
    echo json_encode($response);
    exit;
}

// اگر روش GET یا چیز دیگری بود
echo json_encode(['message' => 'خوش آمدید به Bug Tracker API']);
