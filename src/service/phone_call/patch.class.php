<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\phone_call;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Special service for handling
 */
class patch extends \cenozo\service\patch
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    parent::prepare();

    if( 0 === intval( $this->get_resource_value( 0 ) ) && 404 == $this->status->get_code() )
      $this->status->set_code( 307 ); // temporary redirect since the user has no open phone_call
  }

  /**
   * Override parent method
   */
  protected function create_resource( $index )
  {
    return 'phone_call' == $this->get_subject( $index ) &&
           0 === intval( $this->get_resource_value( $index ) ) ?
      lib::create( 'business\session' )->get_user()->get_open_phone_call() :
      parent::create_resource( $index );
  }
}
