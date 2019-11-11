# Changelog
======
1.3.6
======
- FIX:  Issue with strange meta keys

======
1.3.5
======
- NEW:  4 x new Filters:
		woocommerce_print_products_title
		woocommerce_print_products_short_description
		woocommerce_print_products_price
		woocommerce_print_products_description
- FIX:  Added BR tag after custom meta key
- FIX:  Added CSS classes to custom meta keys

======
1.3.4
======
- NEW: Get paramter product_id will override the shortcodes id attribute

======
1.3.3
======
- NEW: WPML Support 
- NEW: New option "Data to show" > "Try executing shortcodes in description"
	   Disable this if you have issues with shortcodes in your post_content

=====
1.3.2
======
- NEW:  Shortcode support – Example:
		[print_product id="76" mode="pdf" text="Print Product (ID 76)"]
- NEW:  Shortcode rendering in header / footer

=====
1.3.1
======
- FIX:  Plugin initial code updated in order to use hooks

=====
1.3.0
======
- NEW:  Support for Custom Post Fields (see data to show)
	    All custom meta keys for products will be shown there
- NEW:	Added New Font Families: 
		Droid Sans, Droid Serif, Lato, Lora, Merriweather, 
		Montserrat, Open sans, Open Sans Condensed, Oswald, 
		PT Sans, Source Sans Pro, Slabo, Raleway
- NEW: 	Limit access to specific user roles
- FIX:  Small Tweaks

=====
1.2.6
======
- FIX: Print functionality

=====
1.2.5
======
- FIX: WooCommerce 3.0 variable products compatibility

=====
1.2.4
======
- FIX: Plugin activation check
- FIX: WooCommerce 3.0 compatibility
- FIX: Gallery Images overwritten by custom filter

=====
1.2.3
======
- NEW: Shortcode support in short description

=====
1.2.2
======
- FIX: Removed comments from PDF file when viewed in Chrome

=====
1.2.1
======
- FIX: For old PHP Version

=====
1.2.0
======
- NEW: You can now add a custom Text after the Header 
- NEW: You can now add a custom Text before the Footer 
- NEW: Template Nr. 3 has arrived -> see Layouts
- NEW: You can now include / exclude products
- NEW: You can now include / exclude product categories
- NEW: Custom Meta Free Text can be added. This will be placed after the short description
- NEW: Debug Mode (this will prevent PDF from render and display the plain HTML)
- NEW: Set a custom Feature Image size
- NEW: Added many CSS classes to better use the Custom CSS
- FIX: Font-size and Line Height issue (switched to PX)

=====
1.1.8
======
- NEW: Updated MPDF Library to Version 6.1 (this also removes PHP 7 errors)
- NEW: decreased plugin size by 10MB (removed 2 fonts)

=====
1.1.7
======
- NEW: Better plugin activation
- FIX: Better advanced settings page (ACE Editor for CSS and JS )
- FIX: array key exists

=====
1.1.6
======
- FIX: Redux Error

=====
1.1.5
======
- NEW: Removed the embedded Redux Framework for update consistency
//* PLEASE MAKE SURE YOU INSTALL THE REDUX FRAMEWORK PLUGIN *//

======
1.1.4
======
- FIX: Remove shortcodes from description
- FIX: Print Function fixes for certain browsers

======
1.1.3
======
- FIX: Print Function fixes 

======
1.1.2
======
- NEW: Do not display the next pagebreak if an element is empty (e.g. there are no gallery images)
- NEW: Show Title, Caption, Alt Text or Decsription of your Product gallery images
- FIX: Print Function fixes for Safari and Firefox
- FIX: Updated translation files

======
1.1.1
======
- NEW: removed unnecessary files to reduce plugin file size

======
1.1.0
======
- NEW: show product variations
- NEW: show / hide variation image
- NEW: show / hide variation sku
- NEW: show / hide variation description
- NEW: show / hide variation attributes
- NEW: extra class in each title element (e.g. description-title)
- NEW: pagebreak now also in print

======
1.0.9.1
======
- FIX: product upsells title
- FIX: gallery images quality
- FIX: reviews heading text 
- FIX: Russian ruble symbol
- NEW: display a QR-Code blow product short description to your product page
- NEW: display a QR-Code in header / footer to your product page

======
1.0.9
======
- FIX: print windows now closes after print / abort 
- FIX: Word document special characters
- FIX: Paragraph tags are now splitted in table rows
- NEW: set header height 
- NEW: set header top margin
- NEW: set header vertical alignment
- NEW: set foooter height 
- NEW: set foooter top margin
- NEW: set foooter vertical alignment

======
1.0.8
======
- FIX: reviews will now always be displayed text aligned left
- FIX: reviews in print have now valign top
- FIX: image in layout 2 will always be centered
- FIX: text not aligned in layout 1
- NEW: print windows now closes after print / abort

======
1.0.7
======
- FIX: removed unused admin CSS / JS

======
1.0.6
======
- NEW: product gallery images now possible to add
- NEW: custom CSS now will be executed in PDF / Word / Print exports instead of the website
- NEW: product title will now be used for PDF / Word filename

======
1.0.5
======
- FIX: font fix for arabic, chineses and any other special languages

======
1.0.4
======
- NEW: now you have the ability to add pagebreaks yourself

======
1.0.3
======
- FIX: header and footer text alignment

======
1.0.2
======
- FIX: layout images will now be shown in admin UI
- FIX: SKU will now be shown
- NEW: translation of tag / page / categories (please use Loco Translate - Translation comes from WooCommerce itself)
- NEW: line height option for text and heading
- NEW: 3 different header types: 1/1 OR 1/2 + 1/2 OR 1/3 + 1/3 + 1/3
- NEW: 3 different footer types: 1/1 OR 1/2 + 1/2 OR 1/3 + 1/3 + 1/3
- NEW: reorder the product information like you want 
- NEW: ability to choose the text alignment (left, center, right)

======
1.0.1
======
- fixed end of file bug

======
1.0
======
- Inital release

# Future features
- NONE