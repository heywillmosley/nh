function frmMlcmpBackEnd(){

    /**
     * Hide and show the subscribe options in MailChimp action
     *
     * @since 2.03
     */
    function hideOrShowSubscribeOptions() {
        var actionId = this.id.replace( 'frm_mlcmp_address_action_', '' );
        if ( actionId === null || actionId === '' ) {
            return;
        }

        hideOrShowSubscribeOptionsForValue( actionId, this.value );
    }

    /**
     * Hide and show the subscribe options in MailChimp action
     *
     * @since 2.03
     */
    function hideOrShowSubscribeOptionsForValue( actionId, addressAction ) {
        var subscribeOptions = document.getElementById( 'frm_mlcmp_subscribe_options_' + actionId );
        if ( subscribeOptions === null ) {
            return;
        }

        if ( addressAction === 'unsubscribe' ) {
            subscribeOptions.style.display = 'none';
        } else {
            subscribeOptions.style.display = '';
        }
    }

    function frmMlcmpFields(){
        var form_id = jQuery('input[name="id"]').val();
        var id = jQuery(this).val();
        var key = jQuery(this).closest('.frm_single_mailchimp_settings').data('actionkey');
        var div = jQuery(this).closest('.mlchp_list').find('.frm_mlcmp_fields');
        div.empty().append('<span class="spinner frm_mlcmp_loading_field"></span>');
        jQuery('.frm_mlcmp_loading_field').fadeIn('slow');
        jQuery.ajax({
            type:'POST',url:ajaxurl,
            data:{
                action:'frm_mlcmp_match_fields',
                form_id:form_id,
                list_id:id,
                action_key:key},
            success:function(html){
                div.replaceWith(html).css( 'display', '' );
                hideOrShowSubscribeOptionsForValue( key, document.getElementById( 'frm_mlcmp_address_action_' + key ).value );
            }
        });
    }

    function frmMlcmpGetFieldGrpValues(){
        var form_id = jQuery('input[name="id"]').val();
        var field_id = jQuery(this).val();
        if(field_id === ''){
            return false;
        }
        var key = jQuery(this).closest('.frm_single_mailchimp_settings').data('actionkey');
        var list_id = jQuery(this).closest('.mlchp_list').find('select[name$="[list_id]"]').val();
        var grp = jQuery(this).closest('.frm_mlcmp_group_box');
        var grp_id = grp.data('gid');

        jQuery.ajax({
            type:'POST',url:ajaxurl,
            data:{action:'frm_mlcmp_get_group_values', form_id:form_id, list_id:list_id, field_id:field_id, group_id:grp_id,  action_key:key},
            success:function(html){
                grp.find('.frm_mlcmp_group_select').replaceWith(html);
            }
        });
    }

    return{
        init: function(){
            if ( document.getElementById('frm_notification_settings') !== null ) {
                // Bind event handlers for form Settings page
                frmMlcmpBackEndJS.formActionsInit();
            }
        },

        formActionsInit: function(){

            var $formActions = jQuery(document.getElementById('frm_notification_settings'));

            $formActions.on( 'change', '.frm_mlcmp_address_action', hideOrShowSubscribeOptions );
            $formActions.on('change', '.frm_single_mailchimp_settings select[name$="[list_id]"]', frmMlcmpFields);
            $formActions.on('change', 'select.frm_mlcmp_group', frmMlcmpGetFieldGrpValues);
        }
    };
}

var frmMlcmpBackEndJS = frmMlcmpBackEnd();
jQuery(document).ready(function($){
    frmMlcmpBackEndJS.init();
});