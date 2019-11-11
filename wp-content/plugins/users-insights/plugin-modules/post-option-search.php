<?php

class USIN_Post_Option_Search extends USIN_Option_Search{

	public function __construct($post_type, $args = null){
		$this->post_type = $post_type;
		$this->args = $args;

		$key = "usin_".$this->post_type."_search";
		
		parent::__construct($key, array($this, 'get_posts'));
	}

	public function get_posts($number_to_load, $search = null){
		$post_options = array();
		$args = array( 'post_type' => $this->post_type, 'posts_per_page' => $number_to_load );

		if(!empty($this->args)){
			$args = array_merge($args, $this->args);
		}

		if(!empty($search)){
			$args['s'] = $search;
		}
		$posts = get_posts($args);

		foreach ($posts as $post) {
			$post_options[] = array('key'=>$post->ID, 'val'=>$post->post_title);
		}
		return $post_options;
	}

}