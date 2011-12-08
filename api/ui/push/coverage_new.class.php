<?php
/**
 * coverage_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: coverage new
 *
 * Create a new coverage.
 * @package beartooth\ui
 */
class coverage_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    // replace the user_id with an access_id
    if( isset( $args['columns'] ) && isset( $args['columns']['user_id'] ) )
    {
      $user_id = $args['columns']['user_id'];
      $db_site = lib::create( 'business\session' )->get_site();
      $class_name = lib::get_class_name( 'database\role' );
      $db_role = $class_name::get_unique_record( 'name', 'interviewer' );

      $class_name = lib::get_class_name( 'database\access' );
      $db_access = $class_name::get_unique_record(
        array( 'user_id', 'site_id', 'role_id' ),
        array( $user_id, $db_site->id, $db_role->id ) );

      if( is_null( $db_access ) )
        throw lib::create( 'exception\notice',
          sprintf( 'Unable to create coverage for user "%s" since they are not an interviewer.',
                   $db_user->name ),
          __METHOD__ );
      
      unset( $args['columns']['user_id'] );
      $args['columns']['access_id'] = $db_access->id;
    }

    parent::__construct( 'coverage', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   * @throws exception\notice
   */
  public function finish()
  {
    $columns = $this->get_argument( 'columns', array() );

    // validate the postcode
    $postcode_mask = strtoupper( str_replace( ' ', '', $columns['postcode_mask'] ) );
    if( 1 > strlen( $postcode_mask ) || 6 < strlen( $postcode_mask ) )
      throw lib::create( 'exception\notice',
        'Postal codes must contain between 1 and 6 alpha-numeric characters.',
        __METHOD__ );

    // now make sure the postcode is in CNCNCN format, no matter how long it is
    if( ( 1 == strlen( $postcode_mask ) &&
          !preg_match( '/^[A-Z]$/', $postcode_mask ) ) ||
        ( 2 == strlen( $postcode_mask ) &&
          !preg_match( '/^[A-Z][0-9]$/', $postcode_mask ) ) ||
        ( 3 == strlen( $postcode_mask ) &&
          !preg_match( '/^[A-Z][0-9][A-Z]$/', $postcode_mask ) ) ||
        ( 4 == strlen( $postcode_mask ) &&
          !preg_match( '/^[A-Z][0-9][A-Z][0-9]$/', $postcode_mask ) ) ||
        ( 5 == strlen( $postcode_mask ) &&
          !preg_match( '/^[A-Z][0-9][A-Z][0-9][A-Z]$/', $postcode_mask ) ) ||
        ( 6 == strlen( $postcode_mask ) &&
          !preg_match( '/^[A-Z][0-9][A-Z][0-9][A-Z][0-9]$/', $postcode_mask ) ) )
      throw lib::create( 'exception\notice',
        'Invalid postal code format, make sure to start with a letter and that letters '.
        'and numbers alternate.',
        __METHOD__ );

    // add a space after the third character
    if( 3 < strlen( $postcode_mask ) )
      $postcode_mask = substr( $postcode_mask, 0, 3 ).' '.substr( $postcode_mask, 3 );
    
    // add the % at the end, if necessary
    if( 6 > strlen( $postcode_mask ) ) $postcode_mask .= '%';

    $this->arguments['columns']['postcode_mask'] = $postcode_mask;

    parent::finish();
  }
}
?>
