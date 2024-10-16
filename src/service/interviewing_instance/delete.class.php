<?php
/**
 * delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\interviewing_instance;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Extends parent class
 */
class delete extends \cenozo\service\delete
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // make note of the user now so we can delete it after the instance is deleted
    $this->db_user = $this->get_leaf_record()->get_user();
  }

  /**
   * Extends parent method
   */
  protected function finish()
  {
    parent::finish();

    try
    {
      $this->db_user->delete();
    }
    catch( \cenozo\exception\notice $e )
    {
      $this->set_data( $e->get_notice() );
      $this->status->set_code( 306 );
    }
    catch( \cenozo\exception\database $e )
    {
      if( $e->is_referenced() )
      {
        $this->set_data( $e->get_failed_reference_table() );
        $this->status->set_code( 409 );
      }
      else
      {
        $this->status->set_code( 500 );
        throw $e;
      }
    }
  }

  /**
   * Record cache
   * @var database\user
   * @access protected
   */
  protected $db_user = NULL;
}
