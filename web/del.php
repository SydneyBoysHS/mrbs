<?php
// $Id$

require "defaultincludes.inc";

// Get non-standard form variables
$type = get_form_var('type', 'string');
$confirm = get_form_var('confirm', 'string');


// Check the user is authorised for this page
checkAuthorised();

// This is gonna blast away something. We want them to be really
// really sure that this is what they want to do.

if ($type == "room")
{
  // We are supposed to delete a room
  if (isset($confirm))
  {
    // They have confirmed it already, so go blast!
    $commands = array();
    
    // First take out all appointments for this room in the entry table
    // Good for PG
    $commands[] = sql_syntax_delete_from_with_join($tbl_entry,
                                                   $tbl_room_entry,
                                                   "$tbl_entry.id=$tbl_room_entry.entry_id",
                                                   "$tbl_room_entry.room_id=$room");
    
    // Then take out all appointments for this room in the repeat table
    $commands[] = sql_syntax_delete_from_with_join($tbl_repeat,
                                                   $tbl_room_repeat,
                                                   "$tbl_repeat.id=$tbl_room_repeat.repeat_id",
                                                   "$tbl_room_repeat.room_id=$room");
        
    // Finally take out the room itself (the room_entry and room_repeat
    // rows will be deleted by a cascade operation when the corresponding
    // rows from the entry/repeat/room tables are deleted)
    $commands[] = "DELETE FROM $tbl_room WHERE id=$room";
    
    sql_begin();
    foreach ($commands as $command)
    {
      if (sql_command($command) < 0)
      {
        trigger_error(sql_error(), E_USER_WARNING);
        fatal_error(TRUE, get_vocab("fatal_db_error"));
      }
    }
    sql_commit();
   
    // Go back to the admin page
    Header("Location: admin.php?area=$area");
  }
  else
  {
    print_header($day, $month, $year, $area, isset($room) ? $room : "");
   
    // We tell them how bad what they're about to do is
    // Find out how many appointments would be deleted
   
    $sql = "SELECT name, start_time, end_time
              FROM $tbl_entry E, $tbl_room_entry RE
             WHERE E.id=RE.entry_id
               AND RE.room_id=$room";
    $res = sql_query($sql);
    if (! $res)
    {
      trigger_error(sql_error(), E_USER_WARNING);
      fatal_error(FALSE, get_vocab("fatal_db_error"));
    }
    else if (sql_count($res) > 0)
    {
      echo "<p>\n";
      echo get_vocab("deletefollowing") . ":\n";
      echo "</p>\n";
      
      echo "<ul>\n";
      
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        echo "<li>".htmlspecialchars($row['name'])." (";
        echo time_date_string($row['start_time']) . " -> ";
        echo time_date_string($row['end_time']) . ")</li>\n";
      }
      
      echo "</ul>\n";
    }
   
    echo "<div id=\"del_room_confirm\">\n";
    echo "<p>" .  get_vocab("sure") . "</p>\n";
    echo "<div id=\"del_room_confirm_links\">\n";
    echo "<a href=\"del.php?type=room&amp;area=$area&amp;room=$room&amp;confirm=Y\"><span id=\"del_yes\">" . get_vocab("YES") . "!</span></a>\n";
    echo "<a href=\"admin.php\"><span id=\"del_no\">" . get_vocab("NO") . "!</span></a>\n";
    echo "</div>\n";
    echo "</div>\n";
    output_trailer();
  }
}

if ($type == "area")
{
  // We are only going to let them delete an area if there are
  // no rooms. its easier
  $n = sql_query1("SELECT COUNT(*) FROM $tbl_room WHERE area_id=$area");
  if ($n == 0)
  {
    // OK, nothing there, lets blast it away
    sql_command("DELETE FROM $tbl_area WHERE id=$area");
   
    // Redirect back to the admin page
    header("Location: admin.php");
  }
  else
  {
    // There are rooms left in the area
    print_header($day, $month, $year, $area, isset($room) ? $room : "");
    echo "<p>\n";
    echo get_vocab("delarea");
    echo "<a href=\"admin.php\">" . get_vocab("backadmin") . "</a>";
    echo "</p>\n";
    output_trailer();
  }
}

?>
