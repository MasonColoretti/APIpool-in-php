# APIpool

APIpool is a PHP class designed to handle multiple API requests concurrently using cURL. It logs operations and errors to a log file and processes the responses from the APIs.

## Features

- Add and validate API endpoints
- Fetch data concurrently from multiple APIs
- Process and log responses and errors
- Enhanced logging with levels (INFO, ERROR, WARNING)

## Properties

- `array $endpoints`: Stores the URLs of the API endpoints to be fetched.
- `array $responses`: Stores the responses received from the API endpoints.
- `string $logFile`: The path to the log file where messages and errors will be logged.

## Methods

### __construct()

Initializes the `$endpoints`, `$responses`, and `$logFile` properties. Logs the initialization.

### addEndpoint($url)

Adds a new API endpoint URL to the `$endpoints` array after validating it. Logs the operation.

- **Parameters:**
  - `string $url`: The URL of the API endpoint to be added.

### getEndpoints()

Returns the list of added endpoints.

### fetchData()

Fetches data from all the API endpoints in the `$endpoints` array using cURL. Stores the responses in the `$responses` array and logs any errors encountered.

### processResponse($response)

Processes the response by attempting to decode it from JSON. Logs a warning if JSON decoding fails and returns the raw response.

- **Parameters:**
  - `string $response`: The raw response from the API endpoint.

### processResponses()

Processes and prints the responses stored in the `$responses` array. Logs errors if any.

### log($message, $level)

Logs the given message to the log file specified by `$logFile`. The message is prepended with a timestamp and log level.

- **Parameters:**
  - `string $message`: The message to be logged.
  - `string $level`: The level of the log (INFO, ERROR, WARNING).

### run()

Executes the `fetchData()` and `processResponses()` methods to perform the API pool operations.
