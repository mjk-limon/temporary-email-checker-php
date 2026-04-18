<?php

use Ilm\TempMailChecker\Checker;

require 'vendor/autoload.php';

if (isset($_POST['email'])) {
    header('Content-Type: application/json');

    $userCheckApiKey = 'prd_ReyHVn60ZHFPv0vk9FktaK0pXIuB';

    try {
        if (!$email = $_POST['email']) {
            throw new \Exception('Email parameter is required');
        }

        (new Checker(userCheckApiKey: $userCheckApiKey))
            ->checkEmailDomain($email);

        echo json_encode(['success' => true, 'message' => '']);
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
