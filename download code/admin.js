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
    
    // Database Monitor functionality
    if ($('#bfa-activity-monitor').length) {
        // This code should be in the PHP file, not here
        // The activity monitor is already initialized inline in DatabaseMonitor.php
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
