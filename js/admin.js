jQuery(document).ready(function($) {
    $('#test-custom-host').on('click', function(e) {
        e.preventDefault();

        var customHostURL = $('#custom_update_host_url').val();

        if (!customHostURL) {
            $('#test-result').text(SecureUpdatesClient.error_message).css('color', 'red');
            return;
        }

        $('#test-custom-host').attr('disabled', true);
        $('#test-result').text('');

        $.ajax({
            url: SecureUpdatesClient.ajax_url,
            method: 'POST',
            data: {
                action: 'test_custom_host',
                security: SecureUpdatesClient.nonce,
                custom_host_url: customHostURL
            },
            success: function(response) {
                if (response.success) {
                    $('#test-result').text(SecureUpdatesClient.success_message).css('color', 'green');
                } else {
                    $('#test-result').text(SecureUpdatesClient.error_message + ' ' + response.data).css('color', 'red');
                }
                $('#test-custom-host').attr('disabled', false);
            },
            error: function() {
                $('#test-result').text(SecureUpdatesClient.error_message).css('color', 'red');
                $('#test-custom-host').attr('disabled', false);
            }
        });
    });
});
