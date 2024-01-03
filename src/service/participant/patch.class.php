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
  protected function execute()
  {
    parent::execute();

    // update the participant's queue, if requested
    $db_participant = $this->get_leaf_record();
    if( $this->get_argument( 'repopulate', false ) ) $db_participant->repopulate_queue();
  }
}
