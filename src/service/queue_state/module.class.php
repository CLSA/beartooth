<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\queue_state;
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

    if( $select->has_table_columns( 'qnaire' ) )
      $modifier->join( 'qnaire', 'queue_state.qnaire_id', 'qnaire.id' );
  }
}
