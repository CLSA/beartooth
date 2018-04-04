<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\self;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Special service for handling the get meta-resource
 */
class get extends \cenozo\service\self\get
{
  /**
   * Override parent method since self is a meta-resource
   */
  protected function create_resource( $index )
  {
    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );
    $resource = parent::create_resource( $index );

    $setting_sel = lib::create( 'database\select' );
    $setting_sel->from( 'setting' );
    $setting_sel->add_all_table_columns();
    $resource['setting'] = $session->get_setting()->get_column_values( $setting_sel );

    $resource['application']['next_of_kin'] = $setting_manager->get_setting( 'general', 'next_of_kin' );

    if( !is_null( $resource['user']['assignment'] ) )
    {
      $db_assignment = $session->get_user()->get_open_assignment();
      $resource['user']['assignment']['type'] = $db_assignment->get_interview()->get_qnaire()->type;
    }

    return $resource;
  }
}
