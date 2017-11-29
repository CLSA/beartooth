<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\participant;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Special service for handling the patch meta-resource
 */
class patch extends \cenozo\service\participant\patch
{
  /**
   * Override parent method
   */
  public function get_file_as_array()
  {
    // remove next-of-kin data from the patch array
    $patch_array = parent::get_file_as_array();
    foreach( $patch_array as $column => $value )
    {
      if( 'next_of_kin_' == substr( $column, 0, 12 ) )
      {
        $this->next_of_kin[substr( $column, 12 )] = $value;
        unset( $patch_array[$column] );
      }
    }

    return $patch_array;
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    $db_participant = $this->get_leaf_record();

    // process the next-of-kin details
    if( 0 < count( $this->next_of_kin ) )
    {
      $db_next_of_kin = $db_participant->get_next_of_kin();
      if( is_null( $db_next_of_kin ) )
      {
        $db_next_of_kin = lib::create( 'database\next_of_kin' );
        $db_next_of_kin->participant_id = $db_participant->id;
      }
      foreach( $this->next_of_kin as $column => $value ) $db_next_of_kin->$column = $value;
      $db_next_of_kin->save();
    }

    // update the participant's queue, if requested
    if( $this->get_argument( 'repopulate', false ) ) $db_participant->repopulate_queue();
  }

  /**
   * Next of kin information passed to the service
   * @var array
   * @access protected
   */
  protected $next_of_kin = array();
}
