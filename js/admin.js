function bfp_admin()
{
	if(typeof bfp_admin_evaluated != 'undefined') return;
	bfp_admin_evaluated = true;

	var $ = jQuery;

    // Special Radio
    $( document ).on(
        'mousedown',
        '.bfp_radio',
        function()
        {
            $(this).data('status', this.checked);
        }
    );

    $( document ).on(
        'click',
        '.bfp_radio',
        function()
        {
            this.checked = !$(this).data('status');
        }
    );

	// Delete buttons
	$( document ).on(
		'click',
		'.bfp-delete',
		function(evt)
		{
			evt.preventDefault();
			$(this).closest('tr').remove();
		}
	);

	// Add button
	$( document ).on(
		'click',
		'.bfp-add',
		function(evt)
		{
			evt.preventDefault();
			var row = '<tr><td><input type="text" class="bfp-file-name" placeholder="'+bfp['File Name']+'" name="_bfp_file_names[]" value="" /></td><td><input type="text" class="bfp-file-url" placeholder="http://" name="_bfp_file_urls[]" value="" /></td><td width="1%"><a href="#" class="btn btn-default button bfp-select-file">'+bfp['Choose file']+'</a></td><td width="1%"><a href="#" class="bfp-delete">'+bfp['Delete']+'</a></td></tr>';
			$(this).closest('table').find('tbody').append(row);
		}
	);

	//
	$( document ).on(
		'change',
		'[name="_bfp_own_demos"]',
		function()
		{
			$('.bfp-demo-files')[ ( this.checked ) ? 'show' : 'hide' ]();
		}
	);

	$('[name="_bfp_own_demos"]').trigger('change');

	// Select file button
	$( document ).on(
		'click',
		'.bfp-select-file',
		function(evt)
		{
			evt.preventDefault();
			var field = $(this).closest('tr').find('.bfp-file-url'),
				media = wp.media(
							{
								title: bfp['Select audio file'],
								library:{ type: 'audio' },
								button: { text: bfp['Select Item'] },
								multiple: false
							}
						).on(
							'select',
							(function( field ){
								return function() {
									var attachment = media.state().get('selection').first().toJSON(),
										url = attachment.url;
									field.val( url );
								};
							})( field )
						).open();
		}
	);

	// Cover section visibility
	function coverSection()
	{
		var v = $('[name="_bfp_player_controls"]:checked').val(),
			c = $('.bfp-on-cover');
		if(v == 'default' || v == 'button') c.show();
		else c.hide();
	}
	
	$(document).on('change', '[name="_bfp_player_controls"]', function(){
		coverSection();
	});
	
	// Analytics integration
	$(document).on('change', '[name="_bfp_analytics_integration"]', function(){
		var v = $('[name="_bfp_analytics_integration"]:checked').val();
		$('.bfp-analytics-g4').css('display', v == 'g' ? 'table-row' : 'none');
		$('[name="_bfp_analytics_property"]').attr('placeholder', v == 'g' ? 'G-XXXXXXXX' : 'UA-XXXXX-Y');
	});
	
	// Cloud Storage Tab Functionality
	$(document).on('click', '.bfp-cloud-tab-btn', function(){
		var tab = $(this).data('tab');
		
		// Update tab buttons
		$('.bfp-cloud-tab-btn').removeClass('bfp-cloud-tab-active');
		$(this).addClass('bfp-cloud-tab-active');
		
		// Update tab panels
		$('.bfp-cloud-tab-panel').removeClass('bfp-cloud-tab-panel-active');
		$('.bfp-cloud-tab-panel[data-panel="' + tab + '"]').addClass('bfp-cloud-tab-panel-active');
	});
	
	// Initialize
	$('[name="_bfp_analytics_integration"]:eq(0)').change();
	coverSection();

	// Main code
	$('.bfp-add').trigger('click');
}

jQuery(bfp_admin);
jQuery(window).on('load', bfp_admin);

// Make BFP_AJAX globally accessible
window.BFP_AJAX = null;

jQuery(function($) {
    // AJAX Settings Save Handler
    window.BFP_AJAX = {
        container: null,
        autoDismissTimeout: 5000,
        
        init: function() {
            // Create notices container if it doesn't exist
            if (!$('.bfp-ajax-notices-container').length) {
                this.container = $('<div class="bfp-ajax-notices-container"></div>');
                $('body').append(this.container);
            } else {
                this.container = $('.bfp-ajax-notices-container');
            }
            
            // Convert existing notices immediately
            var self = this;
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                self.convertExistingNotices();
            }, 50);
            
            // Watch for new notices added via AJAX or page updates
            this.observeNotices();
            
            // Intercept form submission for AJAX (optional - for future use)
            $('form[action*="bandfront-player-settings"]').on('submit', this.handleFormSubmit.bind(this));
            
            // Create saving indicator
            if (!$('.bfp-ajax-saving-indicator').length) {
                $('body').append(
                    '<div class="bfp-ajax-saving-indicator">' +
                        '<span class="bfp-ajax-saving-indicator__spinner"></span>' +
                        (typeof bfp_ajax !== 'undefined' && bfp_ajax.saving_text ? bfp_ajax.saving_text : 'Saving settings...') +
                    '</div>'
                );
            }
        },
        
        convertExistingNotices: function() {
            var self = this;
            // Find all WordPress admin notices on the page
            $('.wrap > .notice, #wpbody-content > .notice').each(function() {
                var $notice = $(this);
                var message = $notice.find('p').first().text().trim();
                var type = 'info';
                
                // Skip empty messages
                if (!message) return;
                
                // Determine notice type
                if ($notice.hasClass('notice-success')) type = 'success';
                else if ($notice.hasClass('notice-error')) type = 'error';
                else if ($notice.hasClass('notice-warning')) type = 'warning';
                
                // Show flashy notice WITHOUT hiding the original
                self.showNotice(type, message);
                
                // Keep original notice visible - don't hide it
                // $notice.hide(); // REMOVED THIS LINE
            });
        },
        
        observeNotices: function() {
            var self = this;
            // Use MutationObserver to watch for dynamically added notices
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        $(mutation.addedNodes).each(function() {
                            if ($(this).hasClass('notice') && $(this).parent().hasClass('wrap')) {
                                var $notice = $(this);
                                var message = $notice.find('p').text();
                                var type = 'info';
                                
                                if ($notice.hasClass('notice-success')) type = 'success';
                                else if ($notice.hasClass('notice-error')) type = 'error';
                                else if ($notice.hasClass('notice-warning')) type = 'warning';
                                
                                // Show flashy notice without hiding original
                                self.showNotice(type, message);
                                // Don't hide original: $notice.hide();
                            }
                        });
                    });
                });
                
                // Observe the wrap container for new notices
                var wrap = document.querySelector('.wrap');
                if (wrap) {
                    observer.observe(wrap, { childList: true });
                }
            }
        },
        
        handleFormSubmit: function(e) {
            // Only prevent default if we have AJAX configuration
            if (typeof bfp_ajax === 'undefined' || !bfp_ajax.ajax_url) {
                return; // Let normal form submission happen
            }
            
            e.preventDefault();
            
            var form = $(e.target);
            var formData = new FormData(form[0]);
            
            // Show saving indicator
            $('.bfp-ajax-saving-indicator').addClass('bfp-ajax-saving-indicator--active');
            
            // Send AJAX request
            $.ajax({
                url: bfp_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: function() {
                    // Hide saving indicator
                    $('.bfp-ajax-saving-indicator').removeClass('bfp-ajax-saving-indicator--active');
                }
            });
        },
        
        handleSuccess: function(response) {
            if (response.success) {
                this.showNotice('success', response.data.message, response.data.details);
            } else {
                this.showNotice('error', response.data.message);
            }
        },
        
        handleError: function(xhr, status, error) {
            this.showNotice('error', (bfp_ajax && bfp_ajax.error_text) || 'An unexpected error occurred. Please try again.');
        },
        
        showNotice: function(type, message, details) {
            if (!message || message.trim() === '') return;
            
            var noticeId = 'bfp-notice-' + Date.now();
            var noticeClass = 'notice notice-' + type + ' is-dismissible';
            
            // Build notice HTML
            var noticeHtml = '<div id="' + noticeId + '" class="bfp-ajax-notice-wrapper">' +
                '<div class="' + noticeClass + '">' +
                    '<p class="bfp-ajax-notice__message">' + this.escapeHtml(message) + '</p>';
            
            // Add details if provided
            if (details && details.length > 0) {
                noticeHtml += '<div class="bfp-ajax-notice__details"><ul>';
                details.forEach(function(detail) {
                    noticeHtml += '<li>' + this.escapeHtml(detail) + '</li>';
                }.bind(this));
                noticeHtml += '</ul></div>';
            }
            
            noticeHtml += '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">' + ((typeof bfp_ajax !== 'undefined' && bfp_ajax.dismiss_text) ? bfp_ajax.dismiss_text : 'Dismiss this notice') + '</span>' +
                '</button></div></div>';
            
            // Add to container
            var $notice = $(noticeHtml);
            this.container.append($notice);
            
            // Make dismissible
            this.makeDismissible($notice);
            
            // Auto-dismiss after timeout
            this.autoDismiss($notice, this.autoDismissTimeout);
        },
        
        makeDismissible: function($notice) {
            $notice.find('.notice-dismiss').on('click', function() {
                this.dismissNotice($notice);
            }.bind(this));
        },
        
        dismissNotice: function($notice) {
            $notice.addClass('bfp-ajax-notice--removing');
            setTimeout(function() {
                $notice.remove();
            }, 300);
        },
        
        autoDismiss: function($notice, timeout) {
            setTimeout(function() {
                if ($notice.length && !$notice.hasClass('bfp-ajax-notice--removing')) {
                    this.dismissNotice($notice);
                }
            }.bind(this), timeout);
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize AJAX handler if we're on the settings page
    if ($('body').hasClass('toplevel_page_bandfront-player-settings') || 
        $('body').hasClass('settings_page_bandfront-player-settings') ||
        window.location.href.indexOf('bandfront-player-settings') > -1) {
        window.BFP_AJAX.init();
    }
});