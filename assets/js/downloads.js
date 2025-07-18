/**
 * Bandfront Player - Downloads JavaScript
 */
jQuery(document).ready(function($) {
    // Dropdown open/close logic
    $('.download-all-files').on('click', function(e) {
        e.preventDefault();
        var $dropdown = $(this).closest('.download-dropdown');
        $('.download-dropdown').not($dropdown).removeClass('open');
        $dropdown.toggleClass('open');
    });
    
    // Close dropdown if clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.download-dropdown').length) {
            $('.download-dropdown').removeClass('open');
        }
    });
    
    // Handle format selection
    $('.format-option').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var format = $this.data('format');
        var $productDiv = $this.closest('.product-downloads');
        var productId = $productDiv.data('product-id');
        var $button = $productDiv.find('.download-all-files');
        var $spinner = $button.find('.spinner');
        var $buttonText = $button.find('.button-text');
        
        // Update UI
        $buttonText.text(bfpDownloads.converting);
        $spinner.show();
        $button.prop('disabled', true);
        $('.download-dropdown').removeClass('open');
        
        // Make AJAX request
        $.ajax({
            url: bfpDownloads.ajaxurl,
            type: 'POST',
            data: {
                action: 'bfp_handle_bulk_audio_processing',
                format: format,
                product_id: productId,
                security: bfpDownloads.nonce
            },
            success: function(response) {
                console.log('Full response:', response);
                
                if (response.success) {
                    console.log('Files converted:', response.data.files_converted);
                    if (response.data.debug_info && response.data.debug_info.length > 0) {
                        console.log('Debug info:', response.data.debug_info);
                    }
                    
                    // Create temporary download link
                    var tempLink = document.createElement('a');
                    tempLink.href = response.data.download_url;
                    tempLink.download = response.data.filename;
                    document.body.appendChild(tempLink);
                    tempLink.click();
                    document.body.removeChild(tempLink);
                    
                    $buttonText.text(bfpDownloads.downloadAllAs);
                } else {
                    alert(bfpDownloads.conversionFailed + ': ' + response.data);
                    console.error('Conversion error:', response.data);
                    $buttonText.text(bfpDownloads.downloadAllAs);
                }
                $spinner.hide();
                $button.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                console.error('Response:', xhr.responseText);
                alert(bfpDownloads.errorOccurred);
                $buttonText.text(bfpDownloads.downloadAllAs);
                $spinner.hide();
                $button.prop('disabled', false);
            }
        });
    });
    
    // Expand/collapse functionality
    $('.expand-button').on('click', function() {
        var expanded = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', !expanded);
        var target = $(this).attr('aria-controls');
        $('#' + target).toggle(!expanded);
        $(this).text(!expanded ? '▲' : '▼');
    });
});
