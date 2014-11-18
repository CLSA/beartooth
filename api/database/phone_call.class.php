<?php
/**
 * phone_call.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * phone_call: record
 */
class phone_call extends \cenozo\database\has_note {}

// define the join to the interview table
$participant_mod = lib::create( 'database\modifier' );
$participant_mod->join(
  'assignment',
  'phone_call.assignment_id',
  'assignment.id' );
$participant_mod->join(
  'interview',
  'assignment.interview_id',
  'interview.id' );
$participant_mod->join(
  'participant',
  'interview.participant_id',
  'participant.id' );
phone_call::customize_join( 'participant', $participant_mod );
