<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\onyx_instance;
use cenozo\lib, cenozo\log, beartooth\util;

class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // force site_id
    $this->get_leaf_record()->site_id = lib::create( 'business\session' )->get_site()->id;
  }

  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    $db_onyx_instance = $this->get_leaf_record();
    if( is_null( $db_onyx_instance->user_id ) )
    { // create a user for this onyx instance
      $role_class_name = lib::get_class_name( 'database\role' );

      $db_site = lib::create( 'business\session' )->get_site();
      $db_role = $role_class_name::get_unique_record( 'name', 'onyx' );
      $db_user = lib::create( 'database\user' );

      $post_object = $this->get_file_as_object();
      foreach( $db_user->get_column_names() as $column_name )
        if( 'id' != $column_name && property_exists( $post_object, $column_name ) )
          $db_user->$column_name = $post_object->$column_name;
      $db_user->name = $post_object->username;
      $db_user->first_name = $db_site->name.' onyx instance';
      $db_user->last_name = $post_object->username;
      $db_user->active = true;
      $db_user->password = util::encrypt( $post_object->password );
      $db_user->save();

      // grant onyx-access to the user
      $db_access = lib::create( 'database\access' );
      $db_access->user_id = $db_user->id;
      $db_access->site_id = $db_site->id;
      $db_access->role_id = $db_role->id;

      $db_onyx_instance->user_id = $db_user->id;
    }
  }

  /**
   * Extends parent method
   */
  protected function finish()
  {
    parent::finish();

    $db_user = $this->get_leaf_record()->get_user();

    // add the user to ldap
    $ldap_manager = lib::create( 'business\ldap_manager' );
    try
    {
      $ldap_manager->new_user(
        $db_user->name,
        $db_user->first_name,
        $db_user->last_name,
        $this->get_file_as_object()->password );
    }
    catch( \cenozo\exception\ldap $e )
    {
      // catch already exists exceptions, no need to report them
      if( !$e->is_already_exists() ) throw $e;
    }
  }
}
