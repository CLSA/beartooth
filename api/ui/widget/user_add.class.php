<?php
/**
 * user_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget user add
 */
class user_add extends \cenozo\ui\widget\user_add
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
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    $role_class_name = lib::get_class_name( 'database\role' );
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $session = lib::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;

    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'name', '!=', 'onyx' );
    $modifier->where( 'tier', '<=', $session->get_role()->tier );
    $roles = array();
    foreach( $role_class_name::select( $modifier ) as $db_role )
      $roles[$db_role->id] = $db_role->name;

    $user_class_name = lib::get_class_name( 'database\user' );
    $languages = array();
    foreach( $user_class_name::get_enum_values( 'language' ) as $language )
      $languages[] = $language;
    $languages = array_combine( $languages, $languages );

    // set the view's items
    $this->set_item( 'role_id', array_search( 'interviewer', $roles ), true, $roles );
    $this->set_item( 'language', 'en', true, $languages );
  }
}
?>
