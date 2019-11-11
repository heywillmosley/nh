<?php
/**
 * A class with functions the perform a backup of WordPress
 *
 * @copyright Copyright (C) 2011-2013 Michael De Wildt. All rights reserved.
 * @author Michael De Wildt (http://www.mikeyd.com.au/)
 * @license This program is free software; you can redistribute it and/or modify
 *		  it under the terms of the GNU General Public License as published by
 *		  the Free Software Foundation; either version 2 of the License, or
 *		  (at your option) any later version.
 *
 *		  This program is distributed in the hope that it will be useful,
 *		  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *		  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *		  GNU General Public License for more details.
 *
 *		  You should have received a copy of the GNU General Public License
 *		  along with this program; if not, write to the Free Software
 *		  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
 */
class ESIGDS_Config
{
	const MAX_HISTORY_ITEMS = 20;

	private
		$db,
		$options,
		$opt_label = 'esigds_options'
        ;

	public function __construct()
	{
		$this->db = ESIGDS_Factory::db();
	}
	
	public function init_options(){
		add_option($this->opt_label, '', null, false);
	}
	public function drop_options(){
		delete_option($this->opt_label);
	}

	public static function get_backup_dir()
	{
		return str_replace('/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR . '/pdf');
	}
	
	public function set_option($name, $value){
		$opts = get_option($this->opt_label);
		$opts[$name] = $value;
		update_option($this->opt_label, $opts);
	}
	public function get_option($name){
       
		$opts = get_option($this->opt_label);
        
		return $opts[$name];
	}

	public function set_option2($name, $value)
	{
		//Short circut if not changed
		if ($this->get_option($name) === $value) {
			return $this;
		}

		$exists = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->prefix}esigds_options WHERE name = %s", $name)
		);

		if (is_null($exists)) {
			$this->db->insert($this->db->prefix . "esigds_options", array(
				'name' => $name,
				'value' => $value,
			));
		} else {
			$this->db->update(
				$this->db->prefix . 'esigds_options',
				array('value' => $value),
				array('name' => $name)
			);
		}

		$this->options[$name] = $value;

		return $this;
	}

	public function get_option2($name, $no_cache = false)
	{
		if (!isset($this->options[$name]) || $no_cache) {
			$this->options[$name] = $this->db->get_var(
				$this->db->prepare("SELECT value FROM {$this->db->prefix}eddds_options WHERE name = %s", $name)
			);
		}

		return $this->options[$name];
	}

	public function get_dropbox_path($source, $file, $root = false)
	{
		$dropbox_location = null;
		if ($this->get_option('store_in_subfolder')){
			$dropbox_location = $this->get_option('dropbox_location');
		}

		if ($root){
			return $dropbox_location;
		}

		$source = rtrim($source, DIRECTORY_SEPARATOR);

		return ltrim(dirname(str_replace($source, $dropbox_location, $file)), DIRECTORY_SEPARATOR);
	}


}