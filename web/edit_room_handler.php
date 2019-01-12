<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised();

// Get non-standard form variables
$new_area = get_form_var('new_area', 'int');
$old_area = get_form_var('old_area', 'int');
$room_name = get_form_var('room_name', 'string');
$room_disabled = get_form_var('room_disabled', 'string');
$sort_key = get_form_var('sort_key', 'string');
$old_room_name = get_form_var('old_room_name', 'string');
$description = get_form_var('description', 'string');
$capacity = get_form_var('capacity', 'int');
$room_admin_email = get_form_var('room_admin_email', 'string');
$custom_html = get_form_var('custom_html', 'string');  // Used for both area and room, but you only ever have one or the other

// Get the information about the fields in the room table
$fields = db()->field_info($tbl_room);

// Get any custom fields
foreach($fields as $field)
{
  switch($field['nature'])
  {
    case 'character':
      $type = 'string';
      break;
    case 'integer':
      // Smallints and tinyints are considered to be booleans
      $type = (isset($field['length']) && ($field['length'] <= 2)) ? 'string' : 'int';
      break;
    // We can only really deal with the types above at the moment
    default:
      $type = 'string';
      break;
  }
  $var = VAR_PREFIX . $field['name'];
  $$var = get_form_var($var, $type);
  if (($type == 'int') && ($$var === ''))
  {
    unset($$var);
  }
  // Turn checkboxes into booleans
  if (($field['nature'] == 'integer') &&
      isset($field['length']) &&
      ($field['length'] <= 2))
  {
    $$var = (empty($$var)) ? 0 : 1;
  }
}

if (empty($capacity))
{
  $capacity = 0;
}


// UPDATE THE DATABASE
// -------------------

if (empty($area))
{
  throw new \Exception('$area is empty');
}

// Intialise the error array
$errors = array();

// Clean up the address list replacing newlines by commas and removing duplicates
$room_admin_email = clean_address_list($room_admin_email);
// Validate email addresses
if (!validate_email_list($room_admin_email))
{
  $errors[] = 'invalid_email';
}

if (empty($errors))
{
  // Used purely for the syntax_casesensitive_equals() call below, and then ignored
  $sql_params = array();

  // Acquire a mutex to lock out others who might be deleting the new area
  if (!db()->mutex_lock($tbl_area))
  {
    fatal_error(get_vocab("failed_to_acquire"));
  }
  // Check the new area still exists
  if (db()->query1("SELECT COUNT(*) FROM $tbl_area WHERE id=? LIMIT 1", array($new_area)) < 1)
  {
    $errors[] = 'invalid_area';
  }
  // If so, check that the room name is not already used in the area
  // (only do this if you're changing the room name or the area - if you're
  // just editing the other details for an existing room we don't want to reject
  // the edit because the room already exists!)
  // [syntax_casesensitive_equals() modifies our SQL params for us, but we do it ourselves to
  //  keep the flow of this elseif block]
  elseif ( (($new_area != $old_area) || ($room_name != $old_room_name))
          && db()->query1("SELECT COUNT(*)
                             FROM $tbl_room
                            WHERE" . db()->syntax_casesensitive_equals("room_name", $room_name, $sql_params) . "
                              AND area_id=?
                            LIMIT 1", array($room_name, $new_area)) > 0)
  {
    $errors[] = 'invalid_room_name';
  }
  // If everything is still OK, update the databasae
  else
  {
    // Convert booleans into 0/1 (necessary for PostgreSQL)
    $room_disabled = (!empty($room_disabled)) ? 1 : 0;
    $sql = "UPDATE $tbl_room SET ";
    $n_fields = count($fields);
    $sql_params = array();
    $assign_array = array();
    foreach ($fields as $field)
    {
      if ($field['name'] != 'id')  // don't do anything with the id field
      {
        switch ($field['name'])
        {
          // first of all deal with the standard MRBS fields
          case 'area_id':
            $assign_array[] = "area_id=?";
            $sql_params[] = $new_area;
            break;
          case 'disabled':
            $assign_array[] = "disabled=?";
            $sql_params[] = $room_disabled;
            break;
          case 'room_name':
            $assign_array[] = "room_name=?";
            $sql_params[] = $room_name;
            break;
          case 'sort_key':
            $assign_array[] = "sort_key=?";
            $sql_params[] = $sort_key;
            break;
          case 'description':
            $assign_array[] = "description=?";
            $sql_params[] = $description;
            break;
          case 'capacity':
            $assign_array[] = "capacity=?";
            $sql_params[] = $capacity;
            break;
          case 'room_admin_email':
            $assign_array[] = "room_admin_email=?";
            $sql_params[] = $room_admin_email;
            break;
          case 'custom_html':
            $assign_array[] = "custom_html=?";
            $sql_params[] = $custom_html;
            break;
          // then look at any user defined fields
          default:
            $var = VAR_PREFIX . $field['name'];
            switch ($field['nature'])
            {
              case 'integer':
                if (!isset($$var) || ($$var === ''))
                {
                  // Try and set it to NULL when we can because there will be cases when we
                  // want to distinguish between NULL and 0 - especially when the field
                  // is a genuine integer.
                  $$var = ($field['is_nullable']) ? null : 0;
                }
                break;
              default:
                // Do nothing
                break;
            }
            $assign_array[] = db()->quote($field['name']) . "=?";
            $sql_params[] = $$var;
            break;
        }
      }
    }
    
    $sql .= implode(",", $assign_array) . " WHERE id=?";
    $sql_params[] = $room;
    db()->command($sql, $sql_params);

    // Release the mutex and go back to the admin page (for the new area)
    db()->mutex_unlock($tbl_area);
    header("Location: admin.php?day=$day&month=$month&year=$year&area=$new_area&room=$room");
    exit();
  }

  // Release the mutex
  db()->mutex_unlock($tbl_area);
}


// Go back to the room form with errors
$query_string = 'room=' . urlencode($room);
foreach ($errors as $error)
{
  $query_string .= '&errors[]=' . urlencode($error);
}
header("Location: edit_room.php?$query_string");
exit;
