<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\interviewing_instance;
use cenozo\lib, cenozo\log, beartooth\util;

class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    $user_class_name = lib::get_class_name( 'database\user' );

    parent::setup();

    $db_interviewing_instance = $this->get_leaf_record();
    if( is_null( $db_interviewing_instance->user_id ) )
    { // create a user for this interviewing instance if it doesn't already exist
      $role_class_name = lib::get_class_name( 'database\role' );

      $db_site = $db_interviewing_instance->get_site();
      $db_role = $role_class_name::get_unique_record( 'name', 'machine' );

      $post_object = $this->get_file_as_object();
      $db_user = $user_class_name::get_unique_record( 'name', $post_object->username );
      if( is_null( $db_user ) )
      {
        // no user by that name so make one
        $db_user = lib::create( 'database\user' );
        foreach( $db_user->get_column_names() as $column_name )
          if( 'id' != $column_name && property_exists( $post_object, $column_name ) )
            $db_user->$column_name = $post_object->$column_name;
        $db_user->name = $post_object->username;
        $db_user->first_name = $db_site->name.' interviewing instance';
        $db_user->last_name = $post_object->username;
        $db_user->active = true;
        $db_user->password = $post_object->password; // hashed in database\user
        $db_user->save();
      }

      // grant interviewing-access to the user
      $db_access = lib::create( 'database\access' );
      $db_access->user_id = $db_user->id;
      $db_access->site_id = $db_site->id;
      $db_access->role_id = $db_role->id;

      $db_interviewing_instance->user_id = $db_user->id;
    }
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    $role_class_name = lib::get_class_name( 'database\role' );

    if( $this->may_continue() )
    {
      $db_user = $this->get_leaf_record()->get_user();
      $db_site = $this->get_leaf_record()->get_site();

      // add the interviewing role to the interviewing instance's user
      $db_access = lib::create( 'database\access' );
      $db_access->user_id = $db_user->id;
      $db_access->site_id = $db_site->id;
      $db_access->role_id = $role_class_name::get_unique_record( 'name', 'machine' )->id;
      $db_access->save();
    }
  }

  /**
   * Extends parent method
   */
  protected function finish()
  {
    parent::finish();

    if( $this->may_continue() )
    {
      $session = lib::create( 'business\session' );
      $db_interviewing_instance = $this->get_leaf_record();
      $db_user = $db_interviewing_instance->get_user();

      // if this is a pine instance then also grant the user machine access to pine
      if( 'pine' == $db_interviewing_instance->type )
      {
        $db_pine_application = $session->get_pine_application();
        if( !is_null( $db_pine_application ) )
        {
          $cenozo_manager = lib::create( 'business\cenozo_manager', $db_pine_application );
          if( $cenozo_manager->exists() )
          {
            // we must complete the transaction (to create the user record) before giving role access to pine
            $session->get_database()->complete_transaction();

            try
            {
              // use the special argument to setup this instance in Pine
              $cenozo_manager->post( sprintf( 'user/name=%s/access?interviewing_instance=1', $db_user->name ) );
            }
            catch( \cenozo\exception\runtime $e )
            {
              throw lib::create( 'exception\notice',
                sprintf(
                  'The interview instance was successfully created, however, there was an error while granting '.
                  'the instance access to Pine.<br/><br/>'.
                  'Please <a target="pine" href="%s/user/view/name=%s">click here</a> and make sure the user '.
                  'has been granted the "machine" role.',
                  $db_pine_application->url,
                  $db_user->name
                ),
                __METHOD__,
                $e
              );
            }
          }
        }
      }
    }
  }
}
