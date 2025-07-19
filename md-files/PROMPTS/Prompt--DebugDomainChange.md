# Debug Domain Change

## Objective
Update all file references in PHP files to match the new plugin structure and fix namespaces.

## Task Instructions

Check each PHP file in the plugin and add the following, if not already there:

### 1. add the following
1. use Bandfront\Utils\Debug;

2. Debug::domain('FOLDERNAME');

only use the folder name you have been given, this is not the filename this is the PSR4 group it is in. if the file has no use statement in, add one in.

