# Debug Domain Change

## Objective
Update all file references in PHP files to match the new plugin structure and fix namespaces.

## Task Instructions

Check each PHP file in the plugin and add the following, if not already there:

### 1. DEBUG USAGE
use Bandfront\Utils\Debug;

// Set the domain for this file according to its folder
Debug::domain('admin');

