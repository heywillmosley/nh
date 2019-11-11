angular.module('usinPartials').run(['$templateCache', function($templateCache) {
  'use strict';

  $templateCache.put('views/partials/checkboxes-field.html',
    "<div>\n" +
    "\n" +
    "	<ul class=\"usin-checkbox-options\">\n" +
    "		<li ng-repeat=\"(key, val) in $ctrl.options\">\n" +
    "			<md-checkbox ng-checked=\"$ctrl.isOptionChecked(key)\" ng-click=\"$ctrl.onOptionClick(key)\" md-no-ink=\"true\"\n" +
    "				aria-label=\"{{val}}\"></md-checkbox>\n" +
    "			{{val}}\n" +
    "		</li>\n" +
    "	</ul>\n" +
    "\n" +
    "\n" +
    "</div>"
  );


  $templateCache.put('views/partials/info-icon.html',
    "<span class=\"usin-icon-info\">\n" +
    "	<md-tooltip class=\"usin-multiline-tooltip\">{{$ctrl.text}}</md-tooltip>\n" +
    "</span>"
  );

}]);
