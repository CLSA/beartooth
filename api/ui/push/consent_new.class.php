<?php
/**
 * consent_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: consent new
 *
 * Create a new consent.
 * @package beartooth\ui
 */
class consent_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    if( array_key_exists( 'noid', $args ) )
    {
      $noid = $args['noid'];
      unset( $args['noid'] );

      //make sure there is sufficient information
      if( !is_array( $noid ) ||
          !array_key_exists( 'participant.uid', $noid ) )
        throw lib::create( 'exception\argument', 'noid', $noid, __METHOD );

      $participant_class_name = lib::get_class_name( 'database\participant' );
      $db_participant =
        $participant_class_name::get_unique_record( 'uid', $noid['participant.uid'] );
      if( is_null( $db_participant ) )
        throw lib::create( 'exception\argument', 'noid', $noid, __METHOD__ );
      $args['id'] = $db_participant->id;
    }

    parent::__construct( 'consent', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // make sure the date column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'date', $columns ) || 0 == strlen( $columns['date'] ) )
      throw lib::create( 'exception\notice', 'The date cannot be left blank.', __METHOD__ );

    $args = $this->arguments;
    unset( $args['columns']['participant_id'] );

    // replace the participant id with a unique key
    $db_participant = lib::create( 'database\participant', $columns['participant_id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // no errors, go ahead and make the change
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'consent', 'new', $args );
  }
}
?>
