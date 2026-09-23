<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\qnaire;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->join(
      'event_type',
      'qnaire.completed_event_type_id',
      'completed_event_type.id',
      '',
      'completed_event_type'
    );

    $modifier->left_join(
      'event_type',
      'qnaire.prev_event_type_id',
      'prev_event_type.id',
      'prev_event_type'
    );
  }
}
