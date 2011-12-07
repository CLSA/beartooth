<?php
/**
 * queue_restriction_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: queue_restriction delete
 * 
 * @package beartooth\ui
 */
class queue_restriction_delete extends base_delete
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'queue_restriction', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure that only top tier roles can remove queue restrictions not belonging to the current site
    $session = util::create( 'business\session' );

    if( 3 != $session->get_role()->tier && $session->get_site()->id != $this->get_record()->site_id )
    {
      throw util::create( 'exception\notice',
        'You do not have access to remove this queue restriction.', __METHOD__ );
    }

    parent::finish();
  }
}
?>
