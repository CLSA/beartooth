<?php
/**
 * onyx_instance_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: onyx_instance edit
 *
 * Edit a onyx_instance.
 * @package beartooth\ui
 */
class onyx_instance_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx_instance', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure that only top tier roles can edit onyx instances not belonging to the current site
    $session = lib::create( 'business\session' );

    if( 3 != $session->get_role()->tier &&
        $session->get_site()->id != $this->get_record()->site_id )
    {
      throw lib::create( 'exception\notice',
        'You do not have access to edit this onyx instance.', __METHOD__ );
    }

    parent::finish();
  }
}
?>
