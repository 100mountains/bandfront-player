jQuery(document).ready(function($) {
    // Main chart instance
    let mainChart = null;
    let playChart = null;
    let memberChart = null;
    
    // Initialize main analytics chart
    function initMainChart() {
        const ctx = document.getElementById('bfa-main-chart');
        if (!ctx) return;
        
        const dateRange = $('#bfa-date-range').val() || 7;
        
        $.get({
            url: bfaAdmin.apiUrl + 'chart',
            data: { days: dateRange },
            headers: {
                'X-WP-Nonce': bfaAdmin.nonce
            },
            success: function(data) {
                if (mainChart) {
                    mainChart.destroy();
                }
                
                mainChart = new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        });
    }
    
    // Load top posts
    function loadTopPosts() {
        const container = $('#bfa-top-posts');
        if (!container.length) return;
        
        const dateRange = $('#bfa-date-range').val() || 7;
        
        container.html('<p>Loading...</p>');
        
        $.get({
            url: bfaAdmin.apiUrl + 'top-posts',
            data: { 
                days: dateRange,
                limit: 10
            },
            headers: {
                'X-WP-Nonce': bfaAdmin.nonce
            },
            success: function(data) {
                let html = '<table class="widefat striped">';
                html += '<thead><tr><th>Page</th><th>Views</th></tr></thead>';
                html += '<tbody>';
                
                data.forEach(function(post) {
                    html += '<tr>';
                    html += '<td><a href="' + post.url + '">' + post.title + '</a></td>';
                    html += '<td>' + post.views.toLocaleString() + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                container.html(html);
            }
        });
    }
    
    // Date range change handler
    $('#bfa-date-range').on('change', function() {
        initMainChart();
        loadTopPosts();
    });
    
    // Initialize dashboard widget chart
    function initDashboardWidget() {
        const ctx = document.getElementById('bfa-dashboard-chart');
        if (!ctx) return;
        
        $.get({
            url: bfaAdmin.apiUrl + 'chart',
            data: { days: 7 },
            headers: {
                'X-WP-Nonce': bfaAdmin.nonce
            },
            success: function(data) {
                new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                display: false
                            },
                            y: {
                                display: false
                            }
                        },
                        elements: {
                            point: {
                                radius: 0
                            }
                        }
                    }
                });
            }
        });
    }
    
    // Initialize play analytics chart
    function initPlayChart() {
        const ctx = document.getElementById('bfa-play-chart');
        if (!ctx) return;
        
        const dateRange = $('#bfa-play-date-range').val() || 7;
        
        $.get({
            url: bfaAdmin.apiUrl + 'chart',
            data: { 
                days: dateRange,
                metric: 'music_plays'
            },
            headers: {
                'X-WP-Nonce': bfaAdmin.nonce
            },
            success: function(data) {
                if (playChart) {
                    playChart.destroy();
                }
                
                // Update dataset label and colors for plays
                if (data.datasets && data.datasets[0]) {
                    data.datasets[0].label = 'Music Plays';
                    data.datasets[0].borderColor = '#8B5CF6';
                    data.datasets[0].backgroundColor = 'rgba(139, 92, 246, 0.1)';
                }
                
                playChart = new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        });
    }
    
    // Load top tracks
    function loadTopTracks() {
        const container = $('#bfa-top-tracks');
        if (!container.length) return;
        
        const dateRange = $('#bfa-play-date-range').val() || 7;
        
        container.html('<p>Loading...</p>');
        
        $.get({
            url: bfaAdmin.apiUrl + 'top-tracks',
            data: { 
                days: dateRange,
                limit: 10
            },
            headers: {
                'X-WP-Nonce': bfaAdmin.nonce
            },
            success: function(data) {
                let html = '<table class="widefat striped">';
                html += '<thead><tr><th>Track</th><th>Plays</th><th>Avg Duration</th></tr></thead>';
                html += '<tbody>';
                
                if (data && data.length > 0) {
                    data.forEach(function(track) {
                        html += '<tr>';
                        html += '<td><a href="' + track.url + '">' + track.title + '</a></td>';
                        html += '<td>' + track.plays.toLocaleString() + '</td>';
                        html += '<td>' + track.avg_duration + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="3">No play data available yet.</td></tr>';
                }
                
                html += '</tbody></table>';
                container.html(html);
            },
            error: function() {
                container.html('<p>No play data available yet. This will populate once tracks are played.</p>');
            }
        });
    }
    
    // Play date range change handler
    $('#bfa-play-date-range').on('change', function() {
        initPlayChart();
        loadTopTracks();
    });
    
    // Initialize member analytics chart
    function initMemberChart() {
        const ctx = document.getElementById('bfa-member-chart');
        if (!ctx) return;
        
        const dateRange = $('#bfa-member-date-range').val() || 7;
        
        $.get({
            url: bfaAdmin.apiUrl + 'member-growth',
            data: { days: dateRange },
            headers: {
                'X-WP-Nonce': bfaAdmin.nonce
            },
            success: function(data) {
                if (memberChart) {
                    memberChart.destroy();
                }
                
                memberChart = new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            },
            error: function() {
                // Show placeholder chart if no data
                if (memberChart) {
                    memberChart.destroy();
                }
                
                memberChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Sample Data',
                            data: [0, 0, 0, 0, 0, 0, 0],
                            borderColor: '#ccc',
                            backgroundColor: 'rgba(200, 200, 200, 0.1)',
                            borderDash: [5, 5]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });
    }
    
    // Load member activity
    function loadMemberActivity() {
        const container = $('#bfa-member-activity');
        if (!container.length) return;
        
        // Only load if container doesn't have placeholder
        if (container.find('.bfa-placeholder-box').length) return;
        
        const dateRange = $('#bfa-member-date-range').val() || 7;
        
        container.html('<p>Loading member activity...</p>');
        
        $.get({
            url: bfaAdmin.apiUrl + 'member-activity',
            data: { days: dateRange },
            headers: {
                'X-WP-Nonce': bfaAdmin.nonce
            },
            success: function(data) {
                // Render member activity data
                let html = '<table class="widefat striped">';
                html += '<thead><tr><th>Member</th><th>Last Active</th><th>Actions</th></tr></thead>';
                html += '<tbody>';
                
                if (data && data.length > 0) {
                    data.forEach(function(member) {
                        html += '<tr>';
                        html += '<td>' + member.name + '</td>';
                        html += '<td>' + member.last_active + '</td>';
                        html += '<td>' + member.action_count + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="3">No member activity data available.</td></tr>';
                }
                
                html += '</tbody></table>';
                container.html(html);
            },
            error: function() {
                container.html('<p>Member activity data requires Bandfront Members plugin.</p>');
            }
        });
    }
    
    // Member date range change handler
    $('#bfa-member-date-range').on('change', function() {
        initMemberChart();
        loadMemberActivity();
    });
    
    // Initialize based on current page
    if ($('#bfa-main-chart').length) {
        initMainChart();
        loadTopPosts();
    }
    
    if ($('#bfa-play-chart').length) {
        initPlayChart();
        loadTopTracks();
    }
    
    if ($('#bfa-member-chart').length) {
        initMemberChart();
        loadMemberActivity();
    }
    
    if ($('#bfa-dashboard-chart').length) {
        initDashboardWidget();
    }
    
    // Auto-refresh every 60 seconds
    setInterval(function() {
        if ($('#bfa-main-chart').length) {
            initMainChart();
            loadTopPosts();
        }
        
        if ($('#bfa-play-chart').length) {
            initPlayChart();
            loadTopTracks();
        }
    }, 60000);
});

// Database Monitor specific functionality
jQuery(document).ready(function($) {
    // Test Events Handler
    $('#bfp-test-events').on('click', function() {
        var $button = $(this);
        var $spinner = $('.bfp-db-test-actions .spinner');
        var $message = $('.bfp-test-message');
        
        $button.prop('disabled', true);
        $spinner.css('visibility', 'visible');
        $message.text('');
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_generate_test_events',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false);
            $spinner.css('visibility', 'hidden');
            
            if (response.success) {
                $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                // Trigger refresh of activity monitor if it exists
                if (typeof loadDbActivity === 'function') {
                    loadDbActivity();
                }
            } else {
                $message.html('<span style="color: #dc3232;">' + (response.data.message || 'Error generating test events') + '</span>');
            }
            
            // Clear message after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).text('').show();
                });
            }, 5000);
        });
    });
    
    // Clean Events Handler
    $('#bfp-clean-events').on('click', function() {
        if (!confirm(bfpDbMonitor.strings.confirm_clean)) {
            return;
        }
        
        var $button = $(this);
        var $spinner = $('.bfp-db-test-actions .spinner');
        var $message = $('.bfp-test-message');
        
        $button.prop('disabled', true);
        $spinner.css('visibility', 'visible');
        $message.text('');
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_clean_test_events',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false);
            $spinner.css('visibility', 'hidden');
            
            if (response.success) {
                $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                // Trigger refresh of activity monitor if it exists
                if (typeof loadDbActivity === 'function') {
                    loadDbActivity();
                }
            } else {
                $message.html('<span style="color: #dc3232;">' + (response.data.message || 'Error cleaning test data') + '</span>');
            }
            
            // Clear message after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).text('').show();
                });
            }, 5000);
        });
    });
    
    // Initialize database activity monitoring
    if ($('#bfp-db-activity-log').length) {
        var activityPaused = false;
        var activityInterval;
        
        function loadDbActivity() {
            if (activityPaused) return;
            
            $.post(bfpDbMonitor.ajax_url, {
                action: 'bfp_get_db_activity',
                nonce: bfpDbMonitor.nonce_ajax
            }, function(response) {
                if (response.success && response.data.activity) {
                    var $log = $('#bfp-db-activity-log');
                    $log.empty();
                    
                    if (response.data.activity.length === 0) {
                        $log.html('<div class="bfp-traffic-empty">' + bfpDbMonitor.strings.no_activity + '</div>');
                    } else {
                        response.data.activity.forEach(function(event) {
                            var $entry = $('<div class="bfp-traffic-entry"></div>');
                            $entry.append('<span class="bfp-traffic-time">' + event.time + '</span>');
                            $entry.append('<span class="bfp-traffic-method bfp-method-' + event.type + '">' + event.type.toUpperCase() + '</span>');
                            $entry.append('<span class="bfp-traffic-route">' + event.object + '</span>');
                            $entry.append('<span class="bfp-traffic-user">' + event.referrer + '</span>');
                            $log.append($entry);
                        });
                    }
                }
            });
        }
        
        // Load initial data
        loadDbActivity();
        
        // Set up auto-refresh
        activityInterval = setInterval(loadDbActivity, 5000);
        
        // Clear button handler
        $('#bfp-clear-db-activity').on('click', function() {
            $('#bfp-db-activity-log').html('<div class="bfp-traffic-empty">' + bfpDbMonitor.strings.cleared + '</div>');
        });
        
        // Pause/resume handler
        $('#bfp-pause-monitor').on('click', function() {
            activityPaused = !activityPaused;
            $(this).text(activityPaused ? bfpDbMonitor.strings.resume : bfpDbMonitor.strings.pause);
            $('.bfp-traffic-status').text(activityPaused ? '● ' + bfpDbMonitor.strings.paused : '● ' + bfpDbMonitor.strings.live);
            if (activityPaused) {
                $('.bfp-traffic-status').css('color', '#666');
            } else {
                $('.bfp-traffic-status').css('color', '#46b450');
                loadDbActivity(); // Refresh immediately on resume
            }
        });
    }
});

// Database Maintenance functionality
jQuery(document).ready(function($) {
    // Clear Caches Handler
    $('#bfp-clear-caches').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text(bfpDbMonitor.strings.cleaning);
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_clear_caches',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false).text('Clear All Caches');
            if (response.success) {
                alert(response.data.message || 'Caches cleared successfully!');
            } else {
                alert(response.data.message || 'Error clearing caches');
            }
        });
    });
    
    // Optimize Tables Handler
    $('#bfp-optimize-tables').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text(bfpDbMonitor.strings.optimizing);
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_optimize_tables',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false).text('Optimize Tables');
            if (response.success) {
                alert(response.data.message || 'Tables optimized successfully!');
            } else {
                alert(response.data.message || 'Error optimizing tables');
            }
        });
    });
    
    // Export Settings Handler
    $('#bfp-export-settings').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text(bfpDbMonitor.strings.exporting);
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_export_settings',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false).text('Export Settings');
            if (response.success && response.data.download_url) {
                window.location.href = response.data.download_url;
            } else {
                alert(response.data.message || 'Error exporting settings');
            }
        });
    });
    
    // Scan Orphaned Data Handler
    $('#bfp-scan-orphaned').on('click', function() {
        var $button = $(this);
        var $results = $('#bfp-orphaned-results');
        var $resultsText = $results.find('.bfp-scan-results');
        
        $button.prop('disabled', true).text(bfpDbMonitor.strings.scanning);
        $results.hide();
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_scan_orphaned',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false).text('Scan for Orphaned Data');
            
            if (response.success) {
                var data = response.data;
                var message = 'Found: ' + data.orphaned_files + ' orphaned files, ' + 
                             data.orphaned_meta + ' orphaned metadata entries.';
                
                if (data.orphaned_files > 0 || data.orphaned_meta > 0) {
                    message += ' <button type="button" class="button button-small" id="bfp-clean-orphaned">Clean Up</button>';
                }
                
                $resultsText.html(message);
                $results.show();
                
                // Add clean handler if button was added
                $('#bfp-clean-orphaned').on('click', function() {
                    if (confirm(bfpDbMonitor.strings.confirm_clean)) {
                        cleanOrphanedData();
                    }
                });
            } else {
                alert(response.data.message || 'Error scanning for orphaned data');
            }
        });
    });
    
    // Clean orphaned data function
    function cleanOrphanedData() {
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_clean_orphaned',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            if (response.success) {
                $('#bfp-orphaned-results').hide();
                alert(response.data.message || 'Orphaned data cleaned successfully!');
            } else {
                alert(response.data.message || 'Error cleaning orphaned data');
            }
        });
    }
});

// Database Monitor functionality
jQuery(document).ready(function($) {
    // Test Events Handler
    $('#bfa-test-events').on('click', function() {
        var $button = $(this);
        var $spinner = $('.bfa-db-test-actions .spinner');
        var $message = $('.bfa-test-message');
        
        $button.prop('disabled', true);
        $spinner.css('visibility', 'visible');
        $message.text('');
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_generate_test_events',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false);
            $spinner.css('visibility', 'hidden');
            
            if (response.success) {
                $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                // Trigger refresh of activity monitor
                if (typeof window.bfpDbMonitor !== 'undefined') {
                    window.bfpDbMonitor.loadActivity();
                }
            } else {
                $message.html('<span style="color: #dc3232;">' + (response.data.message || 'Error generating test events') + '</span>');
            }
            
            // Clear message after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).text('').show();
                });
            }, 5000);
        });
    });
    
    // Clean Events Handler
    $('#bfa-clean-events').on('click', function() {
        if (!confirm(bfpDbMonitor.strings.confirm_clean)) {
            return;
        }
        
        var $button = $(this);
        var $spinner = $('.bfa-db-test-actions .spinner');
        var $message = $('.bfa-test-message');
        
        $button.prop('disabled', true);
        $spinner.css('visibility', 'visible');
        $message.text('');
        
        $.post(bfpDbMonitor.ajax_url, {
            action: 'bfp_clean_test_events',
            nonce: bfpDbMonitor.nonce
        }, function(response) {
            $button.prop('disabled', false);
            $spinner.css('visibility', 'hidden');
            
            if (response.success) {
                $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                // Trigger refresh
                if (typeof window.bfpDbMonitor !== 'undefined') {
                    window.bfpDbMonitor.loadActivity();
                }
            } else {
                $message.html('<span style="color: #dc3232;">' + (response.data.message || 'Error cleaning test data') + '</span>');
            }
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).text('').show();
                });
            }, 5000);
        });
    });
    
    // Database Activity Monitor - only init if monitoring is enabled
    if (bfpDbMonitor.monitoring_enabled && $('#bfa-db-activity-log').length) {
        window.bfpDbMonitor = {
            interval: null,
            paused: false,
            
            init: function() {
                var self = this;
                
                // Load initial data
                this.loadActivity();
                
                // Set up auto-refresh
                this.interval = setInterval(function() {
                    if (!self.paused) {
                        self.loadActivity();
                    }
                }, 5000);
                
                // Clear button
                $('#bfa-clear-db-activity').on('click', function() {
                    $('#bfa-db-activity-log').html('<div class="bfa-traffic-empty">' + bfpDbMonitor.strings.cleared + '</div>');
                });
            },
            
            loadActivity: function() {
                $.post(bfpDbMonitor.ajax_url, {
                    action: 'bfp_get_db_activity',
                    nonce: bfpDbMonitor.nonce
                }, function(response) {
                    if (response.success && response.data.activity) {
                        var $log = $('#bfa-db-activity-log');
                        $log.empty();
                        
                        if (response.data.activity.length === 0) {
                            $log.html('<div class="bfa-traffic-empty">' + bfpDbMonitor.strings.no_activity + '</div>');
                        } else {
                            response.data.activity.forEach(function(event) {
                                var $entry = $('<div class="bfa-traffic-entry"></div>');
                                $entry.append('<span class="bfa-traffic-time">' + event.time + '</span>');
                                $entry.append('<span class="bfa-traffic-method bfa-method-' + event.type + '">' + event.type.toUpperCase() + '</span>');
                                $entry.append('<span class="bfa-traffic-route">' + event.object + '</span>');
                                if (event.value) {
                                    $entry.append('<span class="bfa-traffic-value">= ' + event.value + '</span>');
                                }
                                $entry.append('<span class="bfa-traffic-user">' + event.user + '</span>');
                                $log.append($entry);
                            });
                        }
                    }
                });
            }
        };
        
        // Initialize
        window.bfpDbMonitor.init();
    }
    
    // Sub-tab navigation
    $('.bfp-db-subtabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).data('subtab');
        
        // Remove active class from all tabs
        $('.bfp-db-subtabs .nav-tab').removeClass('nav-tab-active');
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Hide all tab content
        $('.bfp-subtab-content').hide();
        
        // Show target tab content
        $('#' + targetTab + '-subtab').show();
    });
    
    // Collapsible sections in Schema tab
    $(document).on('click', '.bfa-collapsible', function() {
        var $header = $(this);
        var targetId = $header.data('target');
        var $target = $('#' + targetId);
        
        $target.slideToggle(300);
        $header.toggleClass('expanded');
    });
});
