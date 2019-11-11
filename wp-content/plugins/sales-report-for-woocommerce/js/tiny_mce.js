berocket_tiny_mce_ed = false;
(function() {
    /* Register the buttons */
    tinymce.create('tinymce.plugins.SalesReportContent', {
        init : function(ed, url) {
            berocket_tiny_mce_ed = ed;
            /* Adds HTML tag to selected content */
            ed.addButton('berocket_sale_report', {
                text: 'Add Sales Report Content',
                icon: false,
                onclick: function () {
                    berocket_sales_report_tiny_mce_init();
                    var berocket_sales_report_tiny_mce = berocket_sales_report_tiny_mce_data;
                    berocket_tiny_mce_ed.windowManager.open(berocket_sales_report_tiny_mce);
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    /* Start the buttons */
    tinymce.PluginManager.add( 'berocket_sale_report', tinymce.plugins.SalesReportContent );
})();
jQuery(document).ready(function() {
    function brsr_periodicity_change() {
        jQuery('.brsr_periodicity').hide();
        jQuery('.brsr_periodicity_'+$('.brsr_periodicity_type').val()).show();
    }
    jQuery(document).on('change', '.brsr_periodicity_type', brsr_periodicity_change);
    brsr_periodicity_change();
});
