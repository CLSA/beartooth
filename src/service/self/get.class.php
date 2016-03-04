<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
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
    $resource = parent::create_resource( $index );

    $setting_sel = lib::create( 'database\select' );
    $setting_sel->from( 'setting' );
    $setting_sel->add_all_table_columns();
    $resource['setting'] = lib::create( 'business\session' )->get_setting()->get_column_values( $setting_sel );

    return $resource;
  }
}