-- add in the new quota queue
DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM queue );
    IF @test = 105 THEN

      -- drop assignment table's foreign key to queue and recreate the table
      ALTER TABLE assignment DROP FOREIGN KEY fk_assignment_queue_id;
      DROP TABLE IF EXISTS queue ;
      CREATE TABLE IF NOT EXISTS queue (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
        update_timestamp TIMESTAMP NOT NULL ,
        create_timestamp TIMESTAMP NOT NULL ,
        name VARCHAR(45) NOT NULL ,
        title VARCHAR(255) NOT NULL ,
        rank INT UNSIGNED NULL DEFAULT NULL ,
        qnaire_specific TINYINT(1) NOT NULL ,
        parent_queue_id INT UNSIGNED NULL DEFAULT NULL ,
        description TEXT NULL ,
        PRIMARY KEY (id) ,
        UNIQUE INDEX uq_rank (rank ASC) ,
        INDEX fk_parent_queue_id (parent_queue_id ASC) ,
        UNIQUE INDEX uq_name (name ASC) ,
        CONSTRAINT fk_queue_parent_queue_id
          FOREIGN KEY (parent_queue_id )
          REFERENCES queue (id )
          ON DELETE NO ACTION
          ON UPDATE NO ACTION)
      ENGINE = InnoDB;

      INSERT INTO queue SET
      name = "all",
      title = "All Participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = NULL,
      description = "All participants in the database.";

      INSERT INTO queue SET
      name = "finished",
      title = "Finished all questionnaires",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "all" ) AS tmp ),
      description = "Participants who have completed all questionnaires.";

      INSERT INTO queue SET
      name = "ineligible",
      title = "Not eligible to answer questionnaires",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "all" ) AS tmp ),
      description = "Participants who are not eligible to answer questionnaires due to a permanent
      condition, because they are inactive or because they do not have a phone number.";

      INSERT INTO queue SET
      name = "inactive",
      title = "Inactive participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      been marked as inactive.";

      INSERT INTO queue SET
      name = "sourcing required",
      title = "Participants without a phone number",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      no active phone numbers.";

      INSERT INTO queue SET
      name = "refused consent",
      title = "Participants who refused consent",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      refused consent.";

      INSERT INTO queue SET
      name = "deceased",
      title = "Deceased participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      deceased.";

      INSERT INTO queue SET
      name = "deaf",
      title = "Deaf participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are hard
      of hearing.";

      INSERT INTO queue SET
      name = "mentally unfit",
      title = "Mentally unfit participants",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      mentally unfit.";

      INSERT INTO queue SET
      name = "language barrier",
      title = "Participants with a language barrier",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because of a language
      barrier.";

      INSERT INTO queue SET
      name = "age range",
      title = "Participants whose age is outside of the valid range",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because their age is
      not within the valid range.";

      INSERT INTO queue SET
      name = "not canadian",
      title = "Participants who are not Canadian",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are not
      a Canadian citizen.";

      INSERT INTO queue SET
      name = "federal reserve",
      title = "Participants who live on a federal reserve",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they reside
      on a federal reserve.";

      INSERT INTO queue SET
      name = "armed forces",
      title = "Participants who are in the armed forces",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are full
      time members of the armed forces.";

      INSERT INTO queue SET
      name = "institutionalized",
      title = "Participants who are intitutionalized",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      institutionalized.";

      INSERT INTO queue SET
      name = "noncompliant",
      title = "Participants who are not complying to the rules of the study.",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they are
      not complying the rules of the study.  This list may include participants who are being abusive
      to CLSA staff.";

      INSERT INTO queue SET
      name = "other",
      title = "Participants with an undefined condition",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "ineligible" ) AS tmp ),
      description = "Participants who are not eligible for answering questionnaires because they have
      been identified to have an undefined condition (other).";

      INSERT INTO queue SET
      name = "eligible",
      title = "Eligible to answer questionnaires",
      rank = NULL,
      qnaire_specific = false,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "all" ) AS tmp ),
      description = "Participants who are eligible to answer questionnaires.";

      INSERT INTO queue SET
      name = "qnaire",
      title = "Questionnaire",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "eligible" ) AS tmp ),
      description = "Eligible participants who are currently assigned to the questionnaire.";

      INSERT INTO queue SET
      name = "qnaire waiting",
      title = "Waiting to begin",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Eligible participants who are waiting the scheduled cool-down period before
      beginning the questionnaire.";

      INSERT INTO queue SET
      name = "qnaire ready",
      title = "Ready to begin",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Eligible participants who are ready to begin the questionnaire.";

      INSERT INTO queue SET
      name = "appointment",
      title = "Appointment scheduled",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire ready" ) AS tmp ),
      description = "Participants whose interview has been scheduled.";

      INSERT INTO queue SET
      name = "deferred",
      title = "Contact is deferred",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire ready" ) AS tmp ),
      description = "Participants whose contact is deferred until a future date.";

      INSERT INTO queue SET
      name = "restricted",
      title = "Restricted from calling",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire ready" ) AS tmp ),
      description = "Participants whose city, province or postcode have been restricted.";

      INSERT INTO queue SET
      name = "outside calling time",
      title = "Outside calling time",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire ready" ) AS tmp ),
      description = "Participants whose local time is outside of the valid calling hours.";

      INSERT INTO queue SET
      name = "no appointment",
      title = "No appointment",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire ready" ) AS tmp ),
      description = "Participants whose interview has not been scheduled who can be called.";

      INSERT INTO queue SET
      name = "assigned",
      title = "Currently Assigned",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no appointment" ) AS tmp ),
      description = "Participants who are currently assigned to an interviewer.";

      INSERT INTO queue SET
      name = "new participant",
      title = "Never assigned participants",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no appointment" ) AS tmp ),
      description = "Participants who have never been assigned to an interviewer.";

      INSERT INTO queue SET
      name = "new participant available",
      title = "New participants, available",
      rank = 15,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "new participant" ) AS tmp ),
      description = "New participants who are available.";

      INSERT INTO queue SET
      name = "new participant not available",
      title = "New participants, not available",
      rank = 16,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "new participant" ) AS tmp ),
      description = "New participants who are not available.";

      INSERT INTO queue SET
      name = "old participant",
      title = "Previously assigned participants",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no appointment" ) AS tmp ),
      description = "Participants who have been previously assigned.";

      INSERT INTO queue SET
      name = "contacted",
      title = "Last call: contacted",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'contacted'.";

      INSERT INTO queue SET
      name = "contacted waiting",
      title = "Last call: contacted (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted" ) AS tmp ),
      description = "Participants whose last call result was 'contacted' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "contacted available",
      title = "Last call: contacted (available)",
      rank = 1,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted" ) AS tmp ),
      description = "Available participants whose last call result was 'contacted' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "contacted not available",
      title = "Last call: contacted (not available)",
      rank = 2,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "contacted" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'contacted' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "busy",
      title = "Last call: busy line",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'busy'.";

      INSERT INTO queue SET
      name = "busy waiting",
      title = "Last call: busy line (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy" ) AS tmp ),
      description = "Participants whose last call result was 'busy' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "busy available",
      title = "Last call: busy (available)",
      rank = 3,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy" ) AS tmp ),
      description = "Available participants whose last call result was 'busy' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "busy not available",
      title = "Last call: busy (not available)",
      rank = 4,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "busy" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'busy' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "fax",
      title = "Last call: fax line",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'fax'.";

      INSERT INTO queue SET
      name = "fax waiting",
      title = "Last call: fax line (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax" ) AS tmp ),
      description = "Participants whose last call result was 'fax' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "fax available",
      title = "Last call: fax (available)",
      rank = 5,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax" ) AS tmp ),
      description = "Available participants whose last call result was 'fax' and the scheduled call 
      back time has been reached.";

      INSERT INTO queue SET
      name = "fax not available",
      title = "Last call: fax (not available)",
      rank = 6,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "fax" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'fax' and the scheduled call 
      back time has been reached.";

      INSERT INTO queue SET
      name = "not reached",
      title = "Last call: not reached",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'machine message', 'machine no message',
      'not reached', 'disconnected' or 'wrong number'.";

      INSERT INTO queue SET
      name = "not reached waiting",
      title = "Last call: not reached (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached" ) AS tmp ),
      description = "Participants whose last call result was 'machine message', 'machine no message',
      'not reached', 'disconnected' or 'wrong number' and the scheduled call back time has not yet been
      reached.";

      INSERT INTO queue SET
      name = "not reached available",
      title = "Last call: not reached (available)",
      rank = 7,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached" ) AS tmp ),
      description = "Available participants whose last call result was 'machine message',
      'machine no message', 'not reached', 'disconnected' or 'wrong number' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "not reached not available",
      title = "Last call: not reached (not available)",
      rank = 8,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "not reached" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'machine message',
      'machine no message', 'not reached', 'disconnected' or 'wrong number' and the scheduled
      call back time has been reached.";

      INSERT INTO queue SET
      name = "no answer",
      title = "Last call: no answer",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'no answer'.";

      INSERT INTO queue SET
      name = "no answer waiting",
      title = "Last call: no answer (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer" ) AS tmp ),
      description = "Participants whose last call result was 'no answer' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "no answer available",
      title = "Last call: no answer (available)",
      rank = 9,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer" ) AS tmp ),
      description = "Available participants whose last call result was 'no answer' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "no answer not available",
      title = "Last call: no answer (not available)",
      rank = 10,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "no answer" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'no answer' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "hang up",
      title = "Last call: hang up",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'hang up'.";

      INSERT INTO queue SET
      name = "hang up waiting",
      title = "Last call: hang up (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up" ) AS tmp ),
      description = "Participants whose last call result was 'hang up' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "hang up available",
      title = "Last call: hang up (available)",
      rank = 11,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up" ) AS tmp ),
      description = "Available participants whose last call result was 'hang up' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "hang up not available",
      title = "Last call: hang up (not available)",
      rank = 12,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "hang up" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'hang up' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "soft refusal",
      title = "Last call: soft refusal",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "old participant" ) AS tmp ),
      description = "Participants whose last call result was 'soft refusal'.";

      INSERT INTO queue SET
      name = "soft refusal waiting",
      title = "Last call: soft refusal (waiting)",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal" ) AS tmp ),
      description = "Participants whose last call result was 'soft refusal' and the scheduled call back
      time has not yet been reached.";

      INSERT INTO queue SET
      name = "soft refusal available",
      title = "Last call: soft refusal (available)",
      rank = 13,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal" ) AS tmp ),
      description = "Available participants whose last call result was 'soft refusal' and the scheduled call
      back time has been reached.";

      INSERT INTO queue SET
      name = "soft refusal not available",
      title = "Last call: soft refusal (not available)",
      rank = 14,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "soft refusal" ) AS tmp ),
      description = "Unavailable participants whose last call result was 'soft refusal' and the scheduled
      call back time has been reached.";

      -- update the assignments with the new queue IDs
      UPDATE assignment SET queue_id = 29 WHERE queue_id = 30;
      UPDATE assignment SET queue_id = 30 WHERE queue_id = 31;
      UPDATE assignment SET queue_id = 30 WHERE queue_id = 32;
      UPDATE assignment SET queue_id = 34 WHERE queue_id = 39;
      UPDATE assignment SET queue_id = 35 WHERE queue_id = 40;
      UPDATE assignment SET queue_id = 35 WHERE queue_id = 41;
      UPDATE assignment SET queue_id = 38 WHERE queue_id = 47;
      UPDATE assignment SET queue_id = 39 WHERE queue_id = 48;
      UPDATE assignment SET queue_id = 39 WHERE queue_id = 49;
      UPDATE assignment SET queue_id = 42 WHERE queue_id = 55;
      UPDATE assignment SET queue_id = 43 WHERE queue_id = 56;
      UPDATE assignment SET queue_id = 43 WHERE queue_id = 57;
      UPDATE assignment SET queue_id = 46 WHERE queue_id = 63;
      UPDATE assignment SET queue_id = 47 WHERE queue_id = 64;
      UPDATE assignment SET queue_id = 47 WHERE queue_id = 65;
      UPDATE assignment SET queue_id = 50 WHERE queue_id = 71;
      UPDATE assignment SET queue_id = 51 WHERE queue_id = 72;
      UPDATE assignment SET queue_id = 51 WHERE queue_id = 73;
      UPDATE assignment SET queue_id = 46 WHERE queue_id = 79;
      UPDATE assignment SET queue_id = 47 WHERE queue_id = 80;
      UPDATE assignment SET queue_id = 47 WHERE queue_id = 81;
      UPDATE assignment SET queue_id = 46 WHERE queue_id = 87;
      UPDATE assignment SET queue_id = 47 WHERE queue_id = 88;
      UPDATE assignment SET queue_id = 47 WHERE queue_id = 89;
      UPDATE assignment SET queue_id = 54 WHERE queue_id = 95;
      UPDATE assignment SET queue_id = 55 WHERE queue_id = 96;
      UPDATE assignment SET queue_id = 55 WHERE queue_id = 97;
      UPDATE assignment SET queue_id = 58 WHERE queue_id = 103;
      UPDATE assignment SET queue_id = 59 WHERE queue_id = 104;
      UPDATE assignment SET queue_id = 59 WHERE queue_id = 105;

      -- add back assignment's foreign key to the queue table
      ALTER TABLE assignment
      ADD CONSTRAINT fk_assignment_queue_id
      FOREIGN KEY (queue_id) REFERENCES queue (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
