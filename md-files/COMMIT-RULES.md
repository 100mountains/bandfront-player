## Testing & Quality

### 19. Pre-commit Checklist
- [ ] Output is escaped
- [ ] Nonces are verified
- [ ] Errors have try-catch
- [ ] Classes follow single responsibility
- [ ] Hooks are registered in Hooks Manager
- [ ] Documentation is complete

### 20. Common Pitfalls to Avoid
- ❌ Direct database queries without caching
- ❌ Inline styles or JavaScript
- ❌ Logic in hook callbacks
- ❌ Accessing settings without state manager
- ❌ Missing security escaping
- ❌ Tight coupling between classes
- ❌ Global variables (use class properties)
