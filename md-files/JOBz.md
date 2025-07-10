THE JOB:

1. 25 PHP files to review and clean up  
   - Refactor files for PHP best practices  
   - Shorten code wherever possible while maintaining readability  
   - Reduce bloat and make as lightweight as possible  
2. Player Modernisation - see Player Modernisation md  
3. Input sanitation anywhere it needs it (not many places)  
4. Analysis of redundant options between product and global settings  
   - e.g. remove ‘track titles’ checkbox and hardcode as we always want track titles  
   - Check they don't appear on the front page (we don't see them on the front page now) or decide to make the frontpage more like bandcamp - DISCUSS  
5. Add forward and back (i.e. skip to next track or back a track) audio functionality to the main shop page (not the product page which has a full player)  

**WordPress.org Plugin Review Compliance:**  
6. Ensure code meets WordPress plugin directory standards  
7. Nonce Verification Audit: Check all forms/AJAX calls have proper nonce validation  
8. Capability Checks: Verify user permissions on all admin functions  
9. SQL Injection Prevention: Audit any custom database queries  
10. XSS Protection: Check all output is properly escaped  
11. Performance Optimization  
12. Database Query Optimization: Review and optimize any slow queries  
13. Asset Loading: Conditional loading of CSS/JS (only when players are present)  
14. Caching Strategy: Review transient usage and cache invalidation (see suggestion at bottom)  
15. Multi-Browser Testing: Chrome, Firefox, Safari, Edge  
16. Mobile Responsive Testing: Various screen sizes  
17. Audio Format Testing: MP3, WAV, OGG compatibility (already working good)  
18. WooCommerce Version Compatibility: Test with latest WC version (already working good)  
19. WordPress Version Compatibility: Test with latest WP version (already working good)  
20. Error Handling: Graceful handling of missing audio files  
21. Accessibility: ARIA labels, keyboard navigation support  
22. User Feedback: Success/error messages for admin actions  
23. Child Theme Compatibility: Ensure works with Bandfront child theme (it currently is running on it)  

🎛️ Player controls  
24. Play/pause button only  
25. Full controls (progress bar, volume, etc.)  
26. Smart controls (minimal on shop, full on product pages)  
27. 🖼️ Show play buttons on product images  
    (Experimental feature - appearance depends on your theme)  

These player controls need to be looked at and decided upon in the global versus product scope. The way it was previously designed was sloppy. We can probably just get rid of them and set default behaviour since we only work with storefront theme and my child of that.  
Prepare update code and migration ready for wordpress release.  

Additional comments:  

Update code and migration:  

## 🔧 **Minor Suggestions for Enhancement**  

### **1. Add Version Migration**  
Consider adding a version check to migrate old settings:  
```php
if (get_option('bfp_version') !== BFP_VERSION) {
    // Migrate old product-level layout settings to global
    $this->migrate_legacy_settings();
    update_option('bfp_version', BFP_VERSION);
}
```

### **2. Performance Optimization**  
Your file handler and audio processor are well-structured. Consider adding:  
```php
// In BFP_Audio_Processor
private function get_cached_duration($url) {
    $cache_key = 'bfp_duration_' . md5($url);
    return get_transient($cache_key);
}
```


