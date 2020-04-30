function previewFormAlign(args) {

	// bc-form bc-product-form  - entire form - text-align
	// bc-product-form__option-variants--inline  - variant boxes - flex
	// bc-product-form__quantity    - quantity box - flex

	var $form = $('.fl-node-<?php echo $id; ?> .bc-product-form');
	var $labels = $('.fl-node-<?php echo $id; ?> .bc-product-form__option-label');
	var $variants = $('.fl-node-<?php echo $id; ?> .bc-product-form__option-variants--inline');
	var $quantity = $('.fl-node-<?php echo $id; ?> .bc-product-form__quantity');

	switch (args.getValue()) {
		case 'right':
			$form.css('text-align', 'right');
			$labels.css('text-align', 'right');
			$variants.css('justify-content', 'flex-end');
			$quantity.css('justify-content', 'flex-end');
			break;
		case 'center':
			$form.css('text-align', 'center');
			$labels.css('text-align', 'center');
			$variants.css('justify-content', 'center');
			$quantity.css('justify-content', 'center');
			break;
		case 'left':
		default:
			$form.css('text-align', 'left');
			$labels.css('text-align', 'left');
			$variants.css('justify-content', 'flex-start');
			$quantity.css('justify-content', 'flex-start');
			break;
	}


}
