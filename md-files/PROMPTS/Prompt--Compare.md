# Legacy Code Analysis & Migration Assistant

## Purpose
When something isn't working in the new Bootstrap architecture, use this prompt to:
1. Analyze how it worked in the OLD backup code (bfp\ namespace)
2. Understand the original functionality and logic
3. Migrate it properly to the new Bootstrap architecture

## Instructions for Analysis

### Step 1: Provide Context
Tell me:
- **What's broken**: What specific functionality isn't working?
- **Expected behavior**: How should it work?
- **Current error/issue**: What's happening instead?
- **Related feature**: (e.g., audio streaming, player rendering, file handling)

### Step 2: Provide Legacy Code
Share the OLD backup file(s) that contain the working functionality.

### Step 3: I Will Analyze

#### A. Identify Working Logic
```
FUNCTION: [Method name in old code]
PURPOSE: [What it does]
LOGIC FLOW:
1. [Step by step what happens]
2. [Including any special conditions]
3. [Error handling]

KEY VARIABLES:
- $variable1: [what it holds]
- $variable2: [what it holds]

DEPENDENCIES USED:
- $this->mainPlugin->getConfig(): [what settings it accesses]
- $this->mainPlugin->getFiles(): [what file operations]
- etc.
```

#### B. Trace Execution Path
```
ENTRY POINT: [Where this gets called from]
     ↓
[Method/Hook that calls it]
     ↓
[The actual function]
     ↓
OUTPUTS: [What it returns/does]
```

#### C. Find Missing Pieces
Compare OLD vs NEW to identify:
- Missing methods
- Different logic flow  
- Forgotten edge cases
- Missing error handling
- Different data structures

### Step 4: Migration Solution

I'll provide the corrected code following the Bootstrap architecture:

```php
// OLD CODE (How it worked)
namespace bfp\SomeNamespace;

class OldClass {
    private Plugin $mainPlugin;
    
    public function brokenMethod() {
        // The working logic
    }
}

// NEW CODE (Fixed for Bootstrap)
namespace Bandfront\NewNamespace;

class NewClass {
    // Proper dependencies
    
    public function fixedMethod() {
        // Same logic, new architecture
    }
}
```

## Example Analysis Request

```
WHAT'S BROKEN: Audio files won't stream for purchased products
EXPECTED: Users who bought product should get full file stream
CURRENT ISSUE: Getting 403 error even for purchased products
FEATURE: Audio streaming

Here's the OLD working code from backup:
[paste legacy code]

Here's the NEW broken code:
[paste current code]
```

## Common Issues to Check

### 1. Purchase Verification
- OLD: How did `$this->mainPlugin->getWooCommerce()->isUserProduct()` work?
- NEW: Is WooCommerce component properly checking?

### 2. File Access
- OLD: How were file paths resolved?
- NEW: Is FileManager finding the files correctly?

### 3. Hook Registration
- OLD: What hooks were registered in constructor?
- NEW: Are they properly moved to Hooks.php?

### 4. Settings/State
- OLD: What settings were checked?
- NEW: Are we using the right Config keys?

### 5. Authentication
- OLD: How was user access verified?
- NEW: Is the same logic implemented?

## Output Format

```markdown
## Analysis: [Feature Name]

### 🔍 Root Cause
The issue is: [specific problem identified]

### 📜 How It Worked (Legacy)
In the old code:
1. [Step by step explanation]
2. [Key logic points]

### 🔧 What's Missing/Different
- [ ] [Missing method/logic]
- [ ] [Different approach needed]
- [ ] [Forgotten check]

### ✅ Solution
Here's the fixed code:
[Migrated code that works]

### 📝 Additional Changes Needed
- Update [file] to include [what]
- Add [method] to [class]
- Register [hook] in Hooks.php
```

## Quick Checklist

When something doesn't work, check if the new code has:

- [ ] Same conditional checks as old code
- [ ] Same error handling
- [ ] Same data transformations  
- [ ] Same hook priorities
- [ ] Same filter applications
- [ ] Same user permission checks
- [ ] Same file path handling
- [ ] Same return value structure