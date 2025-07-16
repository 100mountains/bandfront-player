
make new files of documentaton throughout this folder of PHP files. do one php file at a time then say BREAK and i will say CONTINUE. call the files[name]-docs.md

## PHP File Documentation for Bandfront Player

Document this PHP file following this exact format:

## File: [filename.php]
[One sentence describing the file's core purpose]

## Location
src/path/to/file.php

## Overview
[2-3 paragraphs explaining what this file does, why it exists, and how it fits into the plugin architecture. Be specific about its role in the music player functionality.]

## Classes and Methods

### Class: [ClassName]
[Brief description of the class purpose]

#### Properties
- `$propertyName` (type): Description of what it stores
- `$anotherProperty` (type): Description and any default values

#### Methods

`methodName($param1, $param2): returnType`
- Purpose: What this method does
- Parameters:
  - $param1 (type): Description
  - $param2 (type): Description  
- Returns: What it returns and when
- Behavior: Step-by-step explanation of what happens inside
- Side effects: Any database writes, file operations, or state changes
- Hooks: Any WordPress actions/filters used or triggered

[Repeat for every method in the class]

## Functions (if any standalone functions exist)

`functionName($params): returnType`
[Same format as methods above]

## Database Interactions
- Reads: List every database query, what tables/meta it accesses
- Writes: List every insert/update/delete operation
- Transients: Any caching mechanisms used

## File Operations
- Reads: What files it accesses and why
- Writes: What files it creates/modifies
- Directories: Any directory operations

## API Endpoints
- Registered: Any REST routes this file creates
- Consumed: Any external APIs it calls

## Configuration Usage
- Settings read: Which config values it accesses via getState()
- Settings written: Which config values it updates
- Inheritance: How it handles product-specific vs global settings

## Dependencies
- WordPress functions: Core WP functions used
- WooCommerce: Any WC-specific functions or data
- Plugin components: Which other plugin classes/components it uses
- External libraries: Any third-party code

## Security Analysis
- Input validation: How user input is sanitized
- Authorization: Capability checks performed
- Output escaping: How data is escaped for display
- Nonces: CSRF protection implementation

## Performance Considerations
- Query efficiency: Are queries optimized?
- Caching: Is appropriate caching in place?
- Memory usage: Any large data operations?
- Bottlenecks: Identified slow operations

## Code Issues and Improvements
- Deprecated usage: Any outdated WordPress/PHP patterns
- Missing validation: Where input isn't properly checked
- Performance fixes: Specific optimization opportunities
- Modern PHP: Where newer PHP features could improve the code
- Architecture: How this could better fit the plugin structure

## Testing Requirements
- Unit tests needed: Specific methods that need testing
- Integration tests: Component interactions to verify
- Edge cases: Unusual scenarios to handle
- Mock requirements: External dependencies to mock

---

IMPORTANT INSTRUCTIONS:
1. Do not use markdown formatting like ** or __ for emphasis
2. Document EVERY class, method, and function in the file
3. Explain the actual implementation logic, not just what it's supposed to do
4. Be specific about WordPress/WooCommerce integrations
5. List concrete issues and fixes, not generic suggestions
6. Keep formatting clean and scannable with consistent indentation
7. Focus on technical accuracy over formatting prettiness