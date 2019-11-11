<?php
/*
 * The Zendesk URL Class
 *
 * This has all helper methods to manipulate urls
 *
 */

class Zendesk_Wordpress_URL {

  /**
   * This is a utility function to get the endpoint called from the URL passed
   */
  public static function getEndpointFromUrl( $url ) {
    return preg_replace( '/http[s?]?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.zendesk.com/', '', $url );
  }
}
