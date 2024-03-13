<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\interviewing_instance;
use cenozo\lib, cenozo\log, beartooth\util;

class patch extends \cenozo\service\patch
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list = array_merge(
      $this->extract_parameter_list,
      ['active', 'password', 'username']
    );

    parent::prepare();
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    $active = $this->get_argument( 'active', NULL );
    $password = $this->get_argument( 'password', NULL );
    $username = $this->get_argument( 'username', NULL );

    if( !is_null( $password ) )
    {
      $user_class_name = lib::get_class_name( 'database\user' );
      $db_user = $this->get_leaf_record()->get_user();
      $ldap_manager = lib::create( 'business\ldap_manager' );
      $ldap_manager->set_user_password( $db_user->name, $password );

      $db_user->password = $password; // hashed in database\user
      $db_user->save();
    }
    else if( !is_null( $active ) || !is_null( $username ) )
    {
      $leaf_record = $this->get_leaf_record();
      if( !is_null( $leaf_record ) )
      {
        $db_user = $leaf_record->get_user();
        if( !is_null( $active ) )
        {
          try
          {
            $db_user->active = $active;
            $this->status->set_code( 200 );
          }
          catch( \cenozo\exception\argument $e )
          {
            $this->status->set_code( 400 );
            throw $e;
          }
        }

        if( !is_null( $username ) )
        {
          try
          {
            $db_user->name = $username;
            $this->status->set_code( 200 );
          }
          catch( \cenozo\exception\argument $e )
          {
            $this->status->set_code( 400 );
            throw $e;
          }
        }

        if( $this->may_continue() )
        {
          try
          {
            $db_user->save();
          }
          catch( \cenozo\exception\notice $e )
          {
            $this->set_data( $e->get_notice() );
            $this->status->set_code( 306 );
          }
          catch( \cenozo\exception\database $e )
          {
            if( $e->is_duplicate_entry() )
            {
              $data = $e->get_duplicate_columns( $db_user->get_class_name() );
              if( 1 == count( $data ) && 'name' == $data[0] ) $data = array( 'username' );
              $this->set_data( $data );
              $this->status->set_code( 409 );
            }
            else
            {
              $this->status->set_code( $e->is_missing_data() ? 400 : 500 );
              throw $e;
            }
          }
        }
      }
    }
  }
}
