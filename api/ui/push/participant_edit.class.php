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

    $record = $this->get_record();
    $columns = $this->get_argument( 'columns', array() );

    $db_next_of_kin = NULL;
    $db_data_collection = NULL;
    $found_next_of_kin = false;
    $found_data_collection = false;
    
    // process next_of_kin and data_collection columns
    foreach( $columns as $column => $value )
    {
      if( false !== strpos( $column, 'next_of_kin_' ) )
      {
        // make sure the next of kin entry exists
        if( is_null( $db_next_of_kin ) )
        {
          $db_next_of_kin = $record->get_next_of_kin();

          if( is_null( $db_next_of_kin ) )
          { // the record doesn't exist, so create it
            $db_next_of_kin = lib::create( 'database\next_of_kin' );
            $db_next_of_kin->participant_id = $record->id;
          }
        }

        $next_of_kin_column = substr( $column, strlen( 'next_of_kin_' ) );
        $db_next_of_kin->$next_of_kin_column = $value;
        $found_next_of_kin = true;
      }
      else if( false !== strpos( $column, 'data_collection_' ) )
      {
        // make sure the next of kin entry exists
        if( is_null( $db_data_collection ) )
        {
          $db_data_collection = $record->get_data_collection();

          if( is_null( $db_data_collection ) )
          { // the record doesn't exist, so create it
            $db_data_collection = lib::create( 'database\data_collection' );
            $db_data_collection->participant_id = $record->id;
          }
        }

        $data_collection_column = substr( $column, strlen( 'data_collection_' ) );
        $db_data_collection->$data_collection_column = $value;
        $found_data_collection = true;
      }
    }

    if( $found_next_of_kin ) $db_next_of_kin->save();
    if( $found_data_collection ) $db_data_collection->save();

    // look for columns which will affect queue status
    $column_list = array(
      'active',
      'gender',
      'state_id',
      'override_quota',
      'age_group_id' );
    foreach( $record->get_cohort()->get_service_list() as $db_service )
      $column_list[] = sprintf( '%s_site_id', $db_service->name );

    if( array_intersect_key( $columns, array_flip( $column_list ) ) )
      $record->update_queue_status();
  }
}
