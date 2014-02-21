<?php
/**
 * callback_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * pull: callback feed
 */
class callback_feed extends \cenozo\ui\pull\base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the callback feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'callback', $args );
  }
  
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    // create a list of callbacks between the feed's start and end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where(
      'participant_site.site_id', '=', lib::create( 'business\session' )->get_site()->id );
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );

    $this->data = array();
    $callback_class_name = lib::get_class_name( 'database\callback' );
    $setting_manager = lib::create( 'business\setting_manager');
    $now_datetime_obj = util::get_datetime_object();
    $today = $now_datetime_obj->format( 'Y-m-d' );

    foreach( $callback_class_name::select( $modifier ) as $db_callback )
    {
      $datetime_obj = util::get_datetime_object( $db_callback->datetime );

      // get the participant and the participant's current qnaire type (home/site)
      $db_participant = $db_callback->get_participant();
      $title = is_null( $db_participant->uid ) || 0 == strlen( $db_participant->uid )
             ? $db_participant->first_name.' '.$db_participant->last_name
             : $db_participant->uid;

      if( $datetime_obj->diff( $now_datetime_obj )->invert ||
          $today == $datetime_obj->format( 'Y-m-d' ) )
      {
        $db_effective_qnaire = $db_participant->get_effective_qnaire();
        $title .= sprintf(
          ' (%s)',
          is_null( $db_effective_qnaire ) ? 'unknown' : $db_effective_qnaire->type );
      }

      $this->data[] = array(
        'id' => $db_callback->id,
        'title' => $title,
        'allDay' => false,
        'start' => $datetime_obj->format( \DateTime::ISO8601 ) );
    }
  }
}
