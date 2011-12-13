<?php
/**
 * onyx_instance_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * pull: onyx_instance primary
 * 
 * @package beartooth\ui
 */
class onyx_instance_primary extends \cenozo\ui\pull\base_primary
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx_instance', $args );
  }
}
?>
