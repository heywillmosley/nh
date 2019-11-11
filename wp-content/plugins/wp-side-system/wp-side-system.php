<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/**
* Plugin Name: WP Side System
* Plugin URI: https://www.thenewhuman.com/
* Description: This function delivers related content to student's sidebar as they progress through their tracks. The functions are based on the slug to cater relative support material.
* Version: 1.0
* Author: William Mosley, Creative Director
* Author URI: http://thenewhuman.com/team
**/


/**
 * @author William Mosley III, Creative Director
 * Side System
 *
 * 
 */
 
function shortcode_sidesystem () {
    
    // Initializing variables
    $sc = array ();
    $sc_data = array();
    $sc_display = array();
    $count = array();
    $count['product'] = 0;
    $count['book'] = 0;
    $count['marketing'] = 0;
	$track;
	$course;
	$lesson;
    $slug = str_replace(home_url(),'',get_permalink()); // Assign slug variables
	$slug_cat = explode('/', $slug);
	
	
	if(isset($slug_cat[2])) $track = $slug_cat[2];
	
	// check if track has - in it if so remove
	// Dash and everything after
	if (strpos($track, '-') !== false) {
        $track = strtolower( substr($track, 0, strpos($track, "-")) );
        
    } // end if track (if you're in the institute)
	
	// Run if you're in BWI area
	if(!empty($track)) {
	    
	    if(isset($slug_cat[4])) $course = $slug_cat[4];
    	$course = strtolower( substr($course, 0, strpos($course, "-")) );
    	
    	if(isset($slug_cat[6])) $lesson = $slug_cat[6];
    	$lesson = strtolower( substr($lesson, 0, strpos($lesson, "-")) );

	    // Create search criteria for form pull
        $search_criteria = array(
            'status' => 'active',
            'field_filters' => array(
            'mode' => 'any',
            array(
                'key'   => '7',
                'value' => $track
                ),
                array(
                'key'   => '8',
                'value' => $course
                ),
                array(
                'key'   => '9',
                'value' => $lesson
                )
            )
        );
        $entries = GFAPI::get_entries( 1, $search_criteria );
        
        // Run through entries and assign to variables
        foreach( $entries as $value) {
            
            $block_track = strtolower($value[7]);
            $block_course = strtolower($value[8]);
            $block_lesson = strtolower($value[9]);
            $type = strtolower($value[3]);
            $title = $value[1];
            $desc = $value[2];
            $link = $value[4];
            $img_url = $value[6];
            
            // Check if there is content lesson specific  
            if($block_track != '-' && $block_course != '-' && $block_lesson != '-' && $track == $block_track && $course == $block_course && $lesson == $block_lesson) {
                
                add_to_scdata($title, $desc, $img_url, $link, $type);
                
                // Found content add to sc_data to later be sorted & rendered
                $sc_data[$type][$count[$type]]['title'] = $title;
                $sc_data[$type][$count[$type]]['desc'] = $desc;
                $sc_data[$type][$count[$type]]['img_url'] = $img_url;
                $sc_data[$type][$count[$type]]['link'] = $link;
                 
                // Increment the associated type counter
                $count[$type] = $count[$type] + 1;
        
            }
            // Check if there is content course specific
            elseif($block_track != '-' && $block_course != '-' && $block_lesson == '-' && $track == $block_track && $course == $block_course) {
                
                // Found content add to sc_data to later be sorted & rendered
                $sc_data[$type][$count[$type]]['title'] = $title;
                $sc_data[$type][$count[$type]]['desc'] = $desc;
                $sc_data[$type][$count[$type]]['img_url'] = $img_url;
                $sc_data[$type][$count[$type]]['link'] = $link;
                 
                // Increment the associated type counter
                $count[$type] = $count[$type] + 1;
                
                
            }
            // Check if there is content track specific
            elseif($block_track != '-' && $block_course == '-' && $block_lesson == '-' && $track == $block_track) {
                
                // Found content add to sc_data to later be sorted & rendered
                $sc_data[$type][$count[$type]]['title'] = $title;
                $sc_data[$type][$count[$type]]['desc'] = $desc;
                $sc_data[$type][$count[$type]]['img_url'] = $img_url;
                $sc_data[$type][$count[$type]]['link'] = $link;
                 
                 // Increment the associated type counter
                 $count[$type] = $count[$type] + 1;
                 
            } // end elseif
        
        } // end foreach entry
        
        // Count how many results for each category
        $product_ct = count($sc_data['product']);
        $book_ct = count($sc_data['book']);
        $marketing_ct = count($sc_data['marketing material']);
        
        if(is_array($sc_data['product'])){
			
			echo '<h4>Supporting Products</h4><hr>';
			
			// Display each type of content
			foreach($sc_data['product'] as $value) {

				sc_display($value['title'], $value['desc'], $value['link'], $value['img_url'], $product_ct);
			}
			
		} // end is array
        
        if(is_array($sc_data['book'])){
			
			echo '<h4>Suggested Reading</h4><hr>';
        
			foreach($sc_data['book'] as $value) {

				sc_display($value['title'], $value['desc'], $value['link'], $value['img_url'], $book_ct);
			}	
			
		} // end is array
        
        if(is_array($sc_data['marketing material'])){
			
			echo '<h4>Marketing Materials</h4><hr>';
        
			foreach($sc_data['marketing material'] as $value) {

				sc_display($value['title'], $value['desc'], $value['link'], $value['img_url'], $marketing_ct);
			}    
			
		}  // end is array    
        
	} // if track isn't empty
	
}
add_shortcode('side-system', 'shortcode_sidesystem');

// Add to display
function sc_display($title, $desc, $link, $img_url = FALSE, $count) {
    
    // Shorten description
    $desc = trunc($desc, 20);
    
    // if count is 1 (md display)
    if($count == 1) {
        $result = "<div class='card sc-card sc-card-md' style='width: 18rem;'>";
	
        if($img_url != FALSE) {
    		$result .= "<a href='$link' target='_blank'><img src='$img_url' class='card-img-top' alt='$title'></a>";
        }
          	
    	$result .= "<div class='card-body'>
            			<a href='$link' target='_blank'><h5 class='card-title'>$title</h5></a>
            			<p class='card-text caption'>$desc</p>
            			<a href='$link' target='_blank' class='btn btn-primary'>View</a>
          			</div>
        		</div>"; 
        		
   } // end count 1
   
   else {
       
       
       $result = "<div class='media sc-card sc-card-sm'>";
       
       if($img_url != FALSE) {
    		$result .= "<a href='$link' target='_blank'><img src='$img_url' class='mr-3' alt='$title'></a>";
        }
        
        $result .= "<div class='media-body'>
                        <a href='$link' target='_blank'><h5 class='mt-0'>$title</h5></a>
                        <p class='caption'>$desc</p>
                        <a href='$link' target='_blank'>View</a>
                    </div>
                </div>";
   }
    
	echo $result;

}

function add_to_scdata($title, $desc, $img_url, $link, $type) {
                
    global $sc_data;
    global $count;
    
    // Found content add to sc_data to later be sorted & rendered
     $sc_data[$type][$count[$type]]['title'] = $title;
     $sc_data[$type][$count[$type]]['desc'] = $desc;
     $sc_data[$type][$count[$type]]['img_url'] = $img_url;
     $sc_data[$type][$count[$type]]['link'] = $link;
     
     // Increment the associated type counter
     $count[$type] = $count[$type] + 1;
    
}

function trunc($phrase, $max_words) {
   $phrase_array = explode(' ',$phrase);
   if(count($phrase_array) > $max_words && $max_words > 0)
      $phrase = implode(' ',array_slice($phrase_array, 0, $max_words)).'...';
   return $phrase;
}