<?php

/**
 * Helper class for working with colors.
 *
 * @class FLColor
 */
final class FLColor {
	
	/**
	 * @method is_hex
	 */ 
	static public function is_hex($hex) 
	{
		if($hex == 'false' || $hex == '#' || empty($hex)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @method hex
	 */ 
	static public function hex($hex) 
	{
		// No hex. Return false.
		if(!self::is_hex($hex)) {
			return 'false';
		}
		// Hex is a string.
		else if(is_string($hex)) {
			return strstr($hex, '#') ? $hex : '#' . $hex;
		}
		// Hex is an array. Return first that's not false.
		else if(is_array($hex)) {
			
			foreach($hex as $key => $value) {
				
				if(!self::is_hex($hex[$key])) {
					continue;
				}
				
				return self::hex($hex[$key]);
			}
		}
		
		return 'false';
	}
	
	/**
	 * @method foreground
	 */ 
	static public function foreground($hex) 
	{   
		if(!self::is_hex($hex)) {
			return 'false';
		}
		
		return self::yiq($hex) >= 128 ? '#000000' : '#ffffff';
	}
	
	/**
	 * @method similar
	 */ 
	static public function similar($levels, $hex)
	{
		if(!self::is_hex($hex)) {
			return 'false';
		}
		
		$yiq = self::yiq($hex);
		$hex = strstr($hex, '#') ? $hex : '#' . $hex;
		
		// Color is light, darken new color.
		if($yiq >= 128) {
			$level  = $levels[0];
			$func   = 'darken';
		}
		// Color is dark but not black, lighten new color.
		elseif($yiq >= 6) {
			$level  = $levels[1];
			$func   = 'lighten';
		}
		// Color is black or close to it, lighten new color.
		else {
			$level  = $levels[2];
			$func   = 'lighten';
		}
		
		return ($level === 0) ? $hex : $func . '(' . $hex . ', ' . $level . '%)';
	}
	
	/**
	 * @method section_bg
	 */ 
	static public function section_bg($type, $content_bg, $custom_bg)
	{
		if($type == 'none') {
			return 'false';
		}
		else if($type == 'content') {
			return $content_bg;
		}
		else {
			return FLColor::hex($custom_bg);
		}
	}
	
	/**
	 * @method section_fg
	 */ 
	static public function section_fg($type, $body_bg, $custom_bg)
	{
		if($type == 'none') {
			return FLColor::foreground($body_bg);
		}
		else if($type == 'content') {
			return 'false';
		}
		else {
			return FLColor::foreground($custom_bg);
		}
	}
	
	/**
	 * @method clean_hex
	 */ 
	static public function clean_hex($hex) 
	{   
		$hex = str_replace('#', '', $hex);
		
		if(strlen($hex) == 3) {
			$hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
		}
		
		return $hex;
	}

	/**
	 * @method yiq
	 */ 
	static public function yiq($hex)
	{
		$hex    = self::clean_hex($hex);
		$r      = hexdec(substr($hex,0,2));
		$g      = hexdec(substr($hex,2,2));
		$b      = hexdec(substr($hex,4,2));
		$yiq    = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return $yiq;
	}
}