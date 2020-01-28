<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\interview\appointment;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Special service for handling the post meta-resource
 */
class post extends \cenozo\service\post
{
  /**
   * Override parent method
   */
  public function get_file_as_array()
  {
    // store non-standard columns into temporary variables
    $post_array = parent::get_file_as_array();

    if( array_key_exists( 'disable_mail', $post_array ) )
    {
      $this->disable_mail = $post_array['disable_mail'];
      unset( $post_array['disable_mail'] );
    }

    return $post_array;
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    // create appointment mail
    $db_appointment = $this->get_leaf_record();
    if( !$this->disable_mail ) $db_appointment->add_mail();
  }

  /**
   * Caching variable
   */
  protected $disable_mail = false;
}
