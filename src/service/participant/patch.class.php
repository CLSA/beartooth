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
  protected function prepare()
  {
    $this->extract_parameter_list = array_merge(
      $this->extract_parameter_list,
      [
        'next_of_kin_first_name',
        'next_of_kin_last_name',
        'next_of_kin_gender',
        'next_of_kin_phone',
        'next_of_kin_street',
        'next_of_kin_city',
        'next_of_kin_province',
        'next_of_kin_postal_code',
      ]
    );

    parent::prepare();
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    $db_participant = $this->get_leaf_record();

    // process the next-of-kin details
    $db_next_of_kin = NULL;
    foreach( $this->extract_parameter_list as $param )
    {
      // only process next_of_kin parameters
      if( !preg_match( '/^next_of_kin_/', $param ) ) continue;

      $value = $this->get_argument( $param, NULL );
      if( !is_null( $value ) )
      {
        // make sure the next of kin record is loaded or created
        if( is_null( $db_next_of_kin ) )
        {
          $db_next_of_kin = $db_participant->get_next_of_kin();
          if( is_null( $db_next_of_kin ) )
          {
            $db_next_of_kin = lib::create( 'database\next_of_kin' );
            $db_next_of_kin->participant_id = $db_participant->id;
          }
        }

        $column = str_replace( 'next_of_kin_', '', $param );
        $db_next_of_kin->$column = $value;
      }
    }

    if( !is_null( $db_next_of_kin ) ) $db_next_of_kin->save();

    // update the participant's queue, if requested
    if( $this->get_argument( 'repopulate', false ) ) $db_participant->repopulate_queue();
  }
}
