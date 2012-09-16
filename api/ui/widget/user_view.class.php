<?php
/**
 * user_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget user view
 */
class user_view extends \cenozo\ui\widget\user_view
{
  /** 
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // create an associative array with everything we want to display about the user
    $this->add_item( 'language', 'enum', 'Language' );
  }

  /**
   * Defines all items in the view.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    // get the enum arrays
    $user_class_name = lib::get_class_name( 'database\user' );
    $languages = array();
    foreach( $user_class_name::get_enum_values( 'language' ) as $language )
      $languages[] = $language;
    $languages = array_combine( $languages, $languages );

    $this->set_item( 'language', $this->get_record()->language, true, $languages );
  }
}
?>
