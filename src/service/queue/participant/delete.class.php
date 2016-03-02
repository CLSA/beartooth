<?php
/**
 * delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\queue\participant;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Extends parent class
 */
class delete extends \cenozo\service\delete
{
  /**
   * Extends parent method
   */
  protected function validate()
  {
    parent::validate();

    if( 300 > $this->status->get_code() )
    {
      // never allow anyone to remove participants from a queue
      $this->status->set_code( 403 );
    }
  }
}
