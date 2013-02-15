<?php
/**
 * participant_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: participant edit
 *
 * Edit a participant.
 */
class participant_edit extends \cenozo\ui\push\participant_edit
{
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $columns = $this->get_argument( 'columns', array() );
    $db_next_of_kin = NULL;
    $found_next_of_kin = false;
    
    // process next_of_kin columns
    foreach( $columns as $column => $value )
    {
      if( false !== strpos( $column, 'next_of_kin_' ) )
      {
        // make sure the next of kin entry exists
        if( is_null( $db_next_of_kin ) )
        {
          $db_next_of_kin = $this->get_record()->get_next_of_kin();

          if( is_null( $db_next_of_kin ) )
          { // the record doesn't exist, so create it
            $db_next_of_kin = lib::create( 'database\next_of_kin' );
            $db_next_of_kin->participant_id = $this->get_record()->id;
          }
        }

        $next_of_kin_column = substr( $column, strlen( 'next_of_kin_' ) );
        $db_next_of_kin->$next_of_kin_column = $value;
        $found_next_of_kin = true;
      }
    }

    if( $found_next_of_kin ) $db_next_of_kin->save();
  }
}
