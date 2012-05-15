<?php
/**
 * participant_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: participant edit
 *
 * Edit a participant.
 * @package beartooth\ui
 */
class participant_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
  }

  // TODO: document
  public function prepare()
  {
    parent::prepare();

    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
  }

  // TODO: document
  public function setup()
  {
    parent::setup();

    $columns = $this->get_argument( 'columns', array() );

    // don't send information 
    if( array_key_exists( 'consent_to_draw_blood', $columns ) )
      $this->set_machine_request_enabled( false );
  }
}
?>
