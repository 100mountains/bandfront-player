Insert debug logging statements using use bfp\Utils\Debug; and Debug::log() throughout this GLOBAL ADMIN PHP file at strategic points. i particularly want to know about filenames its using in its API and where its sending its information. put more human readable logs in please.

DO NOT ADD ANY OTHER CODE OR CHANGE ANYTHING ELSE IN THE FILE

Examples:

// At function entry
Debug::log('ClassName.php:123 Entering functionName()', ['param1' => $param1, 'param2' => $param2]);

// At function exit
Debug::log('ClassName.php:145 Exiting functionName()', ['return' => $result]);

// Before conditionals
Debug::log('ClassName.php:156 Checking condition', ['condition' => $someVar, 'state' => $currentState]);

// API/HTTP requests
Debug::log('ClassName.php:167 Making API request', ['endpoint' => $url, 'method' => 'POST']);

// Database operations
Debug::log('ClassName.php:178 Database query', ['query' => 'SELECT...', 'params' => $params]);

// Important state changes
Debug::log('ClassName.php:189 State change', ['before' => $oldValue, 'after' => $newValue]);

// Errors or exceptions
Debug::log('ClassName.php:201 Error occurred', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

Key Points to Log:

Combine Function entries and exits into one message (probably on exit unless sub optimal) with informative human readable output with parameters
Before/after important conditionals
Loop iterations (sparingly, maybe just first/last)
API requests and responses
Database queries
State mutations
Error conditions
WordPress hooks/filters
File I/O operations
Add // DEBUG-REMOVE at the end of each line so they can be easily removed later.