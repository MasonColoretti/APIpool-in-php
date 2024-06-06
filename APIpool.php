<?php
// api/APIpool.php

/**
 * Class APIpool
 * 
 * Properties:
 * @property array $endpoints Stores the URLs of the API endpoints to be fetched.
 * @property array $responses Stores the responses received from the API endpoints.
 * @property string $logFile The path to the log file where messages and errors will be logged.
 * 
 * Methods:
 * __construct(): Initializes the $endpoints, $responses, and $logFile properties.
 * addEndpoint($url): Adds a new API endpoint URL to the $endpoints array.
 * fetchData(): Fetches data from all the API endpoints in the $endpoints array using cURL. It stores the responses in the $responses array and logs any errors encountered.
 * processResponses(): Processes the responses stored in the $responses array. Currently, it simply prints each response to the console.
 * log($message, $level): Logs the given message to the log file specified by $logFile. The message is prepended with a timestamp and log level.
 * run(): Executes the fetchData() and processResponses() methods to perform the API pool operations.
 * sanitizeUrl($url): Sanitizes the given URL to ensure it is safe.
 * validateResponse($response): Validates the given response to ensure it meets certain criteria.
 */
class APIpool {
    private $endpoints;
    private $responses;
    private $logFile;
    private $logLevel;
    private $logLevels = ['DEBUG', 'INFO', 'WARNING', 'ERROR'];
    
    public function __construct() {
        $this->endpoints = [];
        $this->responses = [];
        $this->logFile = 'api_pool.log'; // Log file path
        $this->log('API pool initialized', 'INFO');
    }

    public function addEndpoint($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $this->endpoints[] = $url;
            $this->log("Added endpoint: $url", 'INFO');
        } else {
            $this->log("Invalid URL provided: $url", 'ERROR');
        }
    }

    public function getEndpoints() {
        return $this->endpoints;
    }

    
    private function fetchData() {
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach ($this->endpoints as $endpoint) {
            $sanitizedUrl = $this->sanitizeUrl($endpoint);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sanitizedUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout after 10 seconds
            curl_setopt($ch, CURLOPT_FAILONERROR, true); // Fail on HTTP errors

            $curlHandles[$endpoint] = $ch;
            curl_multi_add_handle($multiHandle, $ch);
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($curlHandles as $endpoint => $ch) {
            $response = curl_multi_getcontent($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                $this->log("Error fetching data from $endpoint: $error_msg", 'ERROR');
                $this->responses[$endpoint] = ["error" => $error_msg];
            } else {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code == 200 && $this->validateResponse($response)) {
                    $this->responses[$endpoint] = $this->processResponse($response);
                    $this->log("Successfully fetched data from $endpoint", 'INFO');
                } elseif ($http_code == 200) {
                    $this->log("Invalid response from $endpoint", 'ERROR');
                    $this->responses[$endpoint] = ["error" => "Invalid response"];
                } else {
                    $this->log("Error fetching data from $endpoint: HTTP status code $http_code", 'ERROR');
                    $this->responses[$endpoint] = ["error" => "HTTP status code $http_code"];
                }
            }
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);
    }


    private function processResponse($response) {
        $decodedResponse = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedResponse;
        } else {
            $this->log('Failed to decode JSON response, returning raw response', 'WARNING');
            return ["raw" => $response];
        }
    }

    private function processResponses() {
        foreach ($this->responses as $endpoint => $response) {
            if (isset($response['error'])) {
                $this->log("Error for $endpoint: " . $response['error'], 'ERROR');
                echo "Error for $endpoint: " . $response['error'] . "\n";
            } else {
                echo "Response for $endpoint: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
            }
        }
    }

    private function log($message, $level) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    private function sanitizeUrl($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    private function validateResponse($response) {
        // Add your validation logic here. For example, check if the response is not empty and is a valid JSON.
        if (empty($response)) {
            return false;
        }

        json_decode($response);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function run() {
        $this->fetchData();
        $this->processResponses();
    }
}
