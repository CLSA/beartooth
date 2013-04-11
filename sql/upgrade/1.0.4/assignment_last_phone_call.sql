DROP VIEW IF EXISTS assignment_last_phone_call ;
DROP TABLE IF EXISTS assignment_last_phone_call;
CREATE OR REPLACE VIEW assignment_last_phone_call AS
SELECT assignment_1.id as assignment_id, phone_call_1.id as phone_call_id
FROM assignment AS assignment_1
LEFT JOIN phone_call AS phone_call_1
ON assignment_1.id = phone_call_1.assignment_id
AND phone_call_1.start_datetime = (
  SELECT MAX( phone_call_2.start_datetime )
  FROM phone_call AS phone_call_2, assignment AS assignment_2
  WHERE assignment_2.id = phone_call_2.assignment_id
  AND assignment_1.id = assignment_2.id
  AND phone_call_2.end_datetime IS NOT NULL
  GROUP BY assignment_2.id );
