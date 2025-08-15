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

    /**
     * Handle the incoming request
     */
    #[NoReturn]
    public static function handleRequest(): void
    {
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    self::handlePostRequest();
                default:
                    self::sendResponse(['message' => self::DEFAULT_MESSAGE]);
            }
        } catch (InvalidArgumentException $e) {
            self::sendResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            self::sendResponse(['error' => 'خطای سرور: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle POST request to create a new bug
     */
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

    /**
     * Create a new bug record
     */
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

    /**
     * Validate input data
     * @throws InvalidArgumentException
     */
    private static function validateInput(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("فیلد $field فرستاده نشده است یا خالی است");
            }
        }
    }

    /**
     * Get database query builder instance
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

    /**
     * Send JSON response
     */
    #[NoReturn]
    private static function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

BugTrackerApi::handleRequest();