jQuery(document).ready(function($) {
    'use strict';
    
    // API Traffic Monitor
    let trafficInterval;
    const $trafficLog = $('#bfa-traffic-log');
    const $clearButton = $('#bfa-clear-traffic');
    
    function loadApiTraffic() {
        if (!$trafficLog.length) return;
        
        $.ajax({
            url: bfaAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'bfa_get_api_traffic',
                nonce: bfaAdmin.ajaxNonce
            },
            success: function(response) {
                if (response.success && response.data.traffic) {
                    renderTraffic(response.data.traffic);
                }
            }
        });
    }
    
    function renderTraffic(traffic) {
        $trafficLog.empty();
        
        if (traffic.length === 0) {
            $trafficLog.html('<div class="bfa-no-traffic">No API traffic recorded yet.</div>');
            return;
        }
        
        // Reverse to show newest first
        traffic.reverse().forEach(function(request) {
            const time = new Date(request.timestamp).toLocaleTimeString();
            const methodClass = 'bfa-method-' + request.method.toLowerCase();
            
            const $entry = $('<div class="bfa-traffic-entry">');
            $entry.html(`
                <div class="bfa-traffic-time">${time}</div>
                <div class="bfa-traffic-method ${methodClass}">${request.method}</div>
                <div class="bfa-traffic-route">${request.route}</div>
                <div class="bfa-traffic-user">User: ${request.user_id || 'Guest'}</div>
            `);
            
            $trafficLog.append($entry);
        });
    }
    
    // Auto-refresh traffic if on REST API tab
    const currentTab = new URLSearchParams(window.location.search).get('tab');
    if (currentTab === 'rest-api' && $('#bfa-enable-api').is(':checked')) {
        loadApiTraffic();
        trafficInterval = setInterval(loadApiTraffic, 5000); // Refresh every 5 seconds
    }
    
    // Clear traffic
    $clearButton.on('click', function() {
        $trafficLog.html('<div class="bfa-no-traffic">Traffic log cleared.</div>');
    });
    
    // Stop interval when leaving page
    $(window).on('beforeunload', function() {
        if (trafficInterval) {
            clearInterval(trafficInterval);
        }
    });
    
    // Database Activity Monitor
    let dbActivityInterval = null;
    let dbActivityLog = [];
    
    function initDatabaseMonitor() {
        const $log = $('#bfa-db-activity-log');
        const $clearBtn = $('#bfa-clear-db-activity');
        
        if ($log.length === 0) return;
        
        // Clear button
        $clearBtn.on('click', function() {
            dbActivityLog = [];
            renderDbActivity();
        });
        
        // Start monitoring
        loadDbActivity();
        dbActivityInterval = setInterval(loadDbActivity, 5000); // Update every 5 seconds
    }
    
    function loadDbActivity() {
        $.ajax({
            url: bfaAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'bfa_get_db_activity',
                nonce: bfaAdmin.ajaxNonce
            },
            success: function(response) {
                if (response.success && response.data.activity) {
                    // Add new items to the log
                    response.data.activity.forEach(function(item) {
                        // Check if this item already exists
                        const exists = dbActivityLog.some(function(logItem) {
                            return logItem.time === item.time && 
                                   logItem.type === item.type && 
                                   logItem.object === item.object;
                        });
                        
                        if (!exists) {
                            dbActivityLog.unshift(item);
                        }
                    });
                    
                    // Keep only last 100 items
                    dbActivityLog = dbActivityLog.slice(0, 100);
                    
                    renderDbActivity();
                }
            }
        });
    }
    
    function renderDbActivity() {
        const $log = $('#bfa-db-activity-log');
        
        if (dbActivityLog.length === 0) {
            $log.html('<div class="bfa-traffic-empty">No database activity yet...</div>');
            return;
        }
        
        let html = '<div class="bfa-traffic-items">';
        
        dbActivityLog.forEach(function(item) {
            const typeClass = getEventTypeClass(item.type);
            const typeIcon = getEventTypeIcon(item.type);
            
            html += `
                <div class="bfa-traffic-item">
                    <span class="bfa-traffic-time">${item.time}</span>
                    <span class="bfa-traffic-method bfa-method-${typeClass}">${typeIcon} ${item.type}</span>
                    <span class="bfa-traffic-endpoint">${item.object}</span>
                    ${item.value ? `<span class="bfa-traffic-value">= ${item.value}</span>` : ''}
                    <span class="bfa-traffic-referrer">${item.referrer}</span>
                </div>
            `;
        });
        
        html += '</div>';
        $log.html(html);
    }
    
    function getEventTypeClass(type) {
        const typeMap = {
            'pageview': 'get',
            'music_play': 'post',
            'download': 'post',
            'user_login': 'put',
            'user_logout': 'delete',
            'comment': 'post',
            'purchase': 'post',
            'add_to_cart': 'put',
            'scroll': 'get',
            'time_on_page': 'get'
        };
        
        return typeMap[type] || 'get';
    }
    
    function getEventTypeIcon(type) {
        const iconMap = {
            'pageview': '👁️',
            'music_play': '▶️',
            'download': '⬇️',
            'user_login': '🔑',
            'user_logout': '🚪',
            'comment': '💬',
            'purchase': '💰',
            'add_to_cart': '🛒',
            'scroll': '📜',
            'time_on_page': '⏱️'
        };
        
        return iconMap[type] || '📊';
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        // Check if we're on the database monitor tab
        if ($('#bfa-db-activity').length > 0) {
            initDatabaseMonitor();
        }
    });
    
    // Clean up interval when leaving page
    $(window).on('beforeunload', function() {
        if (dbActivityInterval) {
            clearInterval(dbActivityInterval);
        }
    });
    
    // Database Monitor functionality
    let dbMonitor = {
        interval: null,
        paused: false,
        enabled: false,
        
        init: function() {
            // Check if we're on the database monitor tab
            const $container = $('.bfa-database-monitor');
            if ($container.length === 0) return;
            
            // Get monitoring enabled state from data attribute
            this.enabled = $container.data('monitoring-enabled') === true || $container.data('monitoring-enabled') === 'true';
            
            if (this.enabled) {
                this.startMonitoring();
                this.bindEvents();
            }
        },
        
        startMonitoring: function() {
            if (!this.enabled) return;
            
            this.fetchActivity();
            this.interval = setInterval(() => {
                if (!this.paused && this.enabled) {
                    this.fetchActivity();
                }
            }, 3000); // Update every 3 seconds
        },
        
        fetchActivity: function() {
            if (!this.enabled) return;
            
            $.post(bfaAdmin.ajaxUrl, {
                action: 'bfa_get_db_activity',
                nonce: bfaAdmin.ajaxNonce
            }, (response) => {
                if (response.success) {
                    this.updateActivityList(response.data.activity);
                }
            });
        },
        
        updateActivityList: function(activities) {
            const $list = $('.bfa-activity-list');
            
            // Don't update if monitoring is disabled
            if (!this.enabled) {
                return;
            }
            
            $list.empty();
            
            if (activities.length === 0) {
                $list.html('<div class="bfa-no-traffic"><p>No database activity yet...</p></div>');
                return;
            }
            
            activities.forEach(activity => {
                const $item = $('<div class="bfa-activity-item">');
                $item.html(`
                    <span class="bfa-activity-time">${activity.time}</span>
                    <span class="bfa-activity-type">${activity.type}</span>
                    <span class="bfa-activity-object">${activity.object}</span>
                `);
                $list.append($item);
            });
        },
        
        bindEvents: function() {
            $('#bfa-pause-monitor').on('click', () => {
                this.paused = !this.paused;
                $('#bfa-pause-monitor').text(this.paused ? 'Resume' : 'Pause');
                $('.bfa-status-dot').toggleClass('paused', this.paused);
            });
        },
        
        refresh: function() {
            if (this.enabled) {
                this.fetchActivity();
            }
        },
        
        stop: function() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        }
    };
    
    // Initialize Database Monitor if on that tab
    if ($('.bfa-database-monitor').length > 0) {
        dbMonitor.init();
        window.bfaActivityMonitor = dbMonitor; // Make available globally for test actions
    }
    
    // Test event handlers
    $(document).on('click', '#bfa-generate-test-events', function() {
        var $button = $(this);
        var $spinner = $('.bfa-test-actions .spinner');
        var $results = $('#bfa-test-results');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.post(bfaAdmin.ajaxUrl, {
            action: 'bfa_generate_test_events',
            nonce: bfaAdmin.ajaxNonce
        }, function(response) {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
            
            if (response.success) {
                $results.find('.notice')
                    .removeClass('notice-error')
                    .addClass('notice-success');
                $results.find('p').html(response.data.message);
                $results.show();
                
                // Refresh activity monitor
                if (window.bfaActivityMonitor) {
                    window.bfaActivityMonitor.refresh();
                }
            } else {
                $results.find('.notice')
                    .removeClass('notice-success')
                    .addClass('notice-error');
                $results.find('p').text(response.data.message || 'An error occurred');
                $results.show();
            }
        });
    });
    
    $(document).on('click', '#bfa-clean-test-events', function() {
        if (!confirm('Are you sure you want to delete all test data? This cannot be undone.')) {
            return;
        }
        
        var $button = $(this);
        var $spinner = $('.bfa-test-actions .spinner');
        var $results = $('#bfa-test-results');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.post(bfaAdmin.ajaxUrl, {
            action: 'bfa_clean_test_events',
            nonce: bfaAdmin.ajaxNonce
        }, function(response) {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
            
            if (response.success) {
                $results.find('.notice')
                    .removeClass('notice-error')
                    .addClass('notice-success');
                $results.find('p').html(response.data.message);
                $results.show();
                
                // Refresh activity monitor
                if (window.bfaActivityMonitor) {
                    window.bfaActivityMonitor.refresh();
                }
            } else {
                $results.find('.notice')
                    .removeClass('notice-success')
                    .addClass('notice-error');
                $results.find('p').text(response.data.message || 'An error occurred');
                $results.show();
            }
        });
    });
    
    // Clean up intervals when leaving page or switching tabs
    $(window).on('beforeunload', function() {
        if (trafficInterval) {
            clearInterval(trafficInterval);
        }
        if (dbActivityInterval) {
            clearInterval(dbActivityInterval);
        }
        if (dbMonitor.interval) {
            dbMonitor.stop();
        }
    });
    
    // Also stop monitoring when switching tabs
    $('.nav-tab').on('click', function() {
        if (dbMonitor.interval) {
            dbMonitor.stop();
        }
    });
});
