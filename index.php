<?php

use JetBrains\PhpStorm\NoReturn;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;
use Yshabanei\BugTracker\Helpers\Config;

require_once './vendor/autoload.php';

header('Content-Type: application/json');

class BugTrackerApi
{
    private const REQUIRED_FIELDS = ['name', 'user', 'email', 'link'];
    private const DEFAULT_MESSAGE = 'خوش آمدید به Bug Tracker API';

    #[NoReturn]
    public static function handleRequest(): void
    {
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    self::handlePostRequest();
                case 'PUT':
                    self::handlePutRequest();
                case 'GET':
                    self::handleGetRequest();
                case 'DELETE':
                    self::handleDeleteRequest();
                default:
                    self::sendResponse(['message' => self::DEFAULT_MESSAGE]);
            }
        } catch (InvalidArgumentException $e) {
            self::sendResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            self::sendResponse(['error' => 'خطای سرور: ' . $e->getMessage()], 500);
        }
    }

    #[NoReturn]
    private static function handlePostRequest(): void
    {
        $inputData = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($inputData)) {
            self::sendResponse(['error' => 'داده‌های JSON معتبر نیست یا ارسال نشده‌اند'], 400);
        }

        $response = self::createBug($inputData);
        self::sendResponse($response, array_key_exists('error', $response) ? 400 : 201);
    }

    #[NoReturn]
    private static function handlePutRequest(): void
    {
        list($inputData) = self::extracted();

        $response = self::updateBug($inputData);
        self::sendResponse($response);
    }

    #[NoReturn]
    private static function handleGetRequest(): void
    {
        if (empty($_GET['id'])) {
            self::sendResponse(['error' => 'پارامتر id الزامی است'], 400);
        }

        $id = (int)$_GET['id'];
        try {
            $queryBuilder = self::getQueryBuilder();
        } catch (Exception) {

        }
        $bug = $queryBuilder->table('bugs')->find($id);

        if (!$bug) {
            self::sendResponse(['error' => 'رکوردی با این ID یافت نشد'], 404);
        }

        self::sendResponse([
            'id' => $bug->id,
            'name' => $bug->name,
            'user' => $bug->user,
            'email' => $bug->email,
            'link' => $bug->link
        ]);
    }

    #[NoReturn]
    private static function handleDeleteRequest(): void
    {
        list($inputData, $queryBuilder) = self::extracted();

        try {
            $queryBuilder->table('bugs')->where('id', $inputData['id'])->delete();
        } catch (Exception) {

        }

        self::sendResponse(['message' => "Bug with ID {$inputData['id']} deleted successfully"]);
    }

    private static function createBug(array $data): array
    {
        self::validateInput($data);

        try {
            $queryBuilder = self::getQueryBuilder();
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
        } catch (Exception $e) {
            return ['error' => 'خطا در ایجاد رکورد: ' . $e->getMessage()];
        }
    }

    private static function updateBug(array $data): array
    {
        try {
            $queryBuilder = self::getQueryBuilder();
            $bug = $queryBuilder->table('bugs')->find($data['id']);

            if (!$bug) {
                return ['error' => 'رکوردی با این ID یافت نشد'];
            }

            $queryBuilder->table('bugs')
                ->where('id', $data['id'])
                ->update([
                    'name' => $data['name'] ?? $bug->name,
                    'user' => $data['user'] ?? $bug->user,
                    'email' => $data['email'] ?? $bug->email,
                    'link' => $data['link'] ?? $bug->link,
                ]);

            return [
                'id' => $data['id'],
                'name' => $data['name'] ?? $bug->name,
                'user' => $data['user'] ?? $bug->user,
                'email' => $data['email'] ?? $bug->email,
                'link' => $data['link'] ?? $bug->link,
            ];
        } catch (Exception $e) {
            return ['error' => 'خطا در به‌روزرسانی رکورد: ' . $e->getMessage()];
        }
    }

    private static function validateInput(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("فیلد $field فرستاده نشده است یا خالی است");
            }
        }
    }

    /**
     * @throws Exception
     */
    private static function getQueryBuilder(): PDOQueryBuilder
    {
        try {
            $dbConfig = Config::get('database', 'pdo_testing');
            $pdoConnection = new PDODatabaseConnection($dbConfig);
            return new PDOQueryBuilder($pdoConnection->connect());
        } catch (ConfigFileNotFoundException) {
            throw new Exception('پیکربندی دیتابیس یافت نشد');
        } catch (ConfigNotValidException) {
            throw new Exception('پیکربندی دیتابیس معتبر نیست');
        } catch (DatabaseConnectionException) {
            throw new Exception('خطا در اتصال به دیتابیس');
        }
    }

    #[NoReturn]
    private static function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array
     */
    private static function extracted(): array
    {
        $inputData = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($inputData)) {
            self::sendResponse(['error' => 'داده‌های JSON معتبر نیست یا ارسال نشده‌اند'], 400);
        }

        if (empty($inputData['id'])) {
            self::sendResponse(['error' => 'فیلد id الزامی است'], 400);
        }

        try {
            $queryBuilder = self::getQueryBuilder();
        } catch (Exception) {

        }
        $bug = $queryBuilder->table('bugs')->find($inputData['id']);

        if (!$bug) {
            self::sendResponse(['error' => 'رکوردی با این ID یافت نشد'], 404);
        }
        return array($inputData, $queryBuilder);
    }
}

BugTrackerApi::handleRequest();
