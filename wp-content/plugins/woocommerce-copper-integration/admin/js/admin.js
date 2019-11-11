(function($) {
  "use strict";

  $(document).on("ready", function () {
    $('[data-ui-component="wc-copper-setting-tabs"]').tabs();

    $('[data-ui-component="save-integration-settings"]').on('click', function () {
      var $element = $(this);
      var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';

      $element.attr('disabled', 'true');

      $('[data-ui-component="wccoppernotice"]').remove();

      $.post(ajaxUrl, {
        action: 'wcCopperAjaxValidate',
        form: $('[data-ui-component="integration-settings"]').serialize(),
        dataType: 'json'
      })
        .success(function (response) {
          $element.removeAttr('disabled');
          $('#poststuff').before(response);
          $('html, body').animate({ scrollTop: 0 }, 'fast');

          if ($('[data-ui-component="wccoppernotice"]').hasClass('notice-success')
            && $('[data-ui-component="wc-copper-setting-tabs"]').length <= 0
          ) {
            window.location.reload();
          }
        })
        .error(function(xhr, status, error) {
          $element.removeAttr('disabled');

          $('#poststuff').before(
            '<div data-ui-component="wccoppernotice" class="error notice notice-error"><p><strong>Error!</strong>: '
            + 'Server status code '
            + xhr.status
            + ' - '
            + error
            + '</p></div>'
          );
        });

      return false;
    });

    $('[data-ui-component="wc-copper-save-settings"]').on('click', function () {
      var $element = $(this);
      var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';

      $element.attr('disabled', 'true');

      $('[data-ui-component="wccoppernotice"]').remove();

      $.post(ajaxUrl, {
        action: 'wcCopperAjaxSaveSettings',
        form: $element.closest('form').serialize(),
        dataType: 'json'
      })
        .success(function (response) {
          $element.removeAttr('disabled');
          $('#poststuff').before(response);

          $("html, body").animate({ scrollTop: 0 }, "slow");
        });

      return false;
    });
  });

})(jQuery);
