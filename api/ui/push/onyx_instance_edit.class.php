<?php
/**
 * onyx_instance_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx_instance edit
 *
 * Edit a onyx_instance.
 */
class onyx_instance_edit extends \cenozo\ui\push\base_edit
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
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $columns = $this->get_argument( 'columns', array() );

    // check to see if active is in the column list
    if( array_key_exists( 'active', $columns ) ) 
    {   
      $this->active = $columns['active'];
      unset( $this->arguments['columns']['active'] );
    }   
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure that only top tier roles can edit onyx instances not belonging to the current site
    $session = lib::create( 'business\session' );

    if( 3 != $session->get_role()->tier &&
        $session->get_site()->id != $this->get_record()->site_id )
    {
      throw lib::create( 'exception\notice',
        'You do not have access to edit this onyx instance.', __METHOD__ );
    }
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

    $columns = $this->get_argument( 'columns', array() );

    if( !is_null( $this->active ) ) 
    {
      // send the request to change the active column of the instance's user through an operation
      $args = array( 'id' => $this->get_record()->get_user()->id,
                     'columns' => array( 'active' => $this->active ) );
      $db_operation = lib::create( 'ui\push\user_edit', $args );
      $db_operation->process();
    }
  }

  /** 
   * If a site-specific value is being set this member holds its new value.
   * @var string $active
   * @access protected
   */
  protected $active = NULL;
}
?>
