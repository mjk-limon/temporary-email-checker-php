<?php

namespace Ilm\TempMailChecker;

class Checker
{
    public function __construct(
        private $userCheckApiKey,
        private $cacheDir = __DIR__ . '/../.cache',
    ) {
        $this->cacheDir = realpath($this->cacheDir);
    }

    private function getCacheFile($type, $domain)
    {
        $firstChar = $domain[0] ?? '';

        if (is_numeric($firstChar)) {
            return "{$this->cacheDir}/{$type}-others.list";
        }

        return "{$this->cacheDir}/{$type}-{$firstChar}.list";
    }

    private static function getDomainFromEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return null;
        }
        return strtolower(trim($parts[1]));
    }

    private function isDomainInList($domain, $listType)
    {
        $cacheFile = $this->getCacheFile($listType, $domain);

        if (!file_exists($cacheFile)) {
            return false;
        }

        $domains = file($cacheFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array($domain, $domains);
    }

    private function appendDomainToList($domain, $listType)
    {
        if ($this->isDomainInList($domain, $listType)) {
            return true;
        }

        $cacheFile = $this->getCacheFile($listType, $domain);

        if (file_put_contents($cacheFile, $domain . PHP_EOL, FILE_APPEND | LOCK_EX)) {
            return true;
        }

        return false;
    }

    private function checkIfDisposable($domain)
    {
        $url = 'https://api.usercheck.com/domain/' . urlencode($domain);

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $this->userCheckApiKey . "\r\n",
                'timeout' => 10
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \Exception('API call failed');
        }

        $data = json_decode($response, true);
        return $data;
    }

    public function checkEmailDomain($email)
    {
        $domain = $this->getDomainFromEmail($email);

        if ($domain === null) {
            throw new \Exception('Invalid email');
        }

        if ($this->isDomainInList($domain, 'whitelist')) {
            return true;
        }

        if ($this->isDomainInList($domain, 'blacklist')) {
            throw new \Exception('Blacklisted email');
        }

        $apiResponse = $this->checkIfDisposable($domain);
        $isDisposable = isset($apiResponse['disposable']) ? $apiResponse['disposable'] : false;

        if ($isDisposable) {
            $this->appendDomainToList($domain, 'blacklist');

            throw new \Exception('Blacklisted email');
        }

        $this->appendDomainToList($domain, 'whitelist');

        return true;
    }
}
