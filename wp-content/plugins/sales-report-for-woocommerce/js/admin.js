var br_saved_timeout;
var br_savin_ajax = false;
(function ($){
    $(document).ready( function () {
        $(document).on('click', '.br_report_email_link', function(event) {
            event.preventDefault();
            $('.br_report_email').hide();
            $('.br_report_email_'+$(this).data('id')).show();
        });
        $(document).on('click', '.br_send_test_report', function(event) {
            event.preventDefault();
            $.post(ajaxurl, {action: 'br_sales_report_test'}, function (data) {});
        });
        $(document).on('change', '.br_report_email_input_email', function() {
            if( $(this).val() ) {
                $('.br_email_link_'+$(this).data('id')).text($(this).val());
            } else {
                $('.br_email_link_'+$(this).data('id')).text($(this).data('id')+1);
            }
        });
        $(document).on('change', '.br_use_wptime', function() {
            $('.br_time').hide();
            var show_time = '.br_utc_time';
            if( $(this).prop('checked') ) {
                show_time = '.br_wp_time';
            }
            $(show_time).show();
        });
    });
})(jQuery);
