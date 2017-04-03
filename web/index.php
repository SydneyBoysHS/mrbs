<?php
namespace MRBS;

require "defaultincludes.inc";
require_once "mincals.inc";


// Gets the booking interval (start first slot to end last slot)
// for a given view.   Returns an array indexed by 'start' and 'end'.
function get_interval($view, $month, $day, $year)
{
  global $weekstarts;
  
  $result = array();
  
  switch ($view)
  {
    case 'day':
      $result['start'] = get_start_first_slot($month, $day, $year);
      $result['end'] = get_end_last_slot($month, $day, $year);
      break;
      
    case 'week':
      $day_of_week = date('w', mktime(12, 0, 0, $month, $day, $year));
      $days_after_start_of_week = ($day_of_week + DAYS_PER_WEEK - $weekstarts) % 7;
      $result['start'] = get_start_first_slot($month, $day - $days_after_start_of_week, $year);
      $result['end'] = get_end_last_slot($month, $day + (DAYS_PER_WEEK - 1) - $days_after_start_of_week, $year);
      break;
      
    case 'month':
      $result['start'] = get_start_first_slot($month, 1, $year);
      $result['end'] = get_end_last_slot($month + 1, 0, $year);
      break;
      
    default:
      trigger_error("Unsupported view: '$view'", E_USER_NOTICE);
      break;
  }
  
  return $result;
}


// $interval needs to be a whole number of booking days,
// ie start at the beginning of a booking day and end at the end.
function get_empty_map($area, $interval)
{
  $result = array();
  
  // First get all the slots in this interval
  $date = new DateTime();
  $date->setTimestamp($interval['start']);
  
  $room_result = array();
  
  do {
    $j = $date->format('j');  // day, no leading zero
    $n = $date->format('n');  // month, no leading zero
    $Y = $date->format('Y');  // year, four digits
    
    $slots = get_slot_starts($n, $j, $Y);
    foreach ($slots as $slot)
    {
      $room_result[$slot] = array();
    }
    $date->modify('+1 day');
  } while (get_end_last_slot($n, $j, $Y) < $interval['end']);
  
  // Now do this for every room in the area
  $rooms = get_rooms($area);
  foreach ($rooms as $room)
  {
    $result[$room['id']] = $room_result;
  }
  
  return $result;
}


function get_map($area, $entries, $interval)
{
  global $resolution;
 
  $map = get_empty_map($area, $interval);
  $slots = array_keys(current($map));
  
  foreach ($entries as $entry)
  {
    $room_id = $entry['room_id'];
    while (($slot = current($slots)) !== false)
    {
      // Find the first occupied slot
      // We need the end_time condition to cut out bookings that fall
      // entirely between booking days.
      if (($entry['start_time'] < ($slot + $resolution))  &&
          ($entry['end_time'] > $slot))
      {
        // and then find the remaining occupied slots
        do {
          $map[$room_id][$slot][] = array('id' => $entry['id'],
                                          'type' => $entry['type'],
                                          'name' => $entry['name'],
                                          'description' => $entry['description']);
          $slot = next($slots);
        } while (($slot !== false) && ($entry['end_time'] > $slot));
        reset($slots);
        break;
      }
      $slot = next($slots);
    }
    reset($slots);
  }

  return $map;
}


function get_cell_html($content, $class, $n_slots=1)
{
  $html = '';
  
  $html .= "<td class=\"$class\"";
  if ($n_slots > 1)
  {
    $html .= " colspan=\"$n_slots\"";
  }
  $html .= ">$content</td>\n";
  
  return $html;
}


function get_row_labels_table($map)
{
  $html = '';
  
  $html .= "<table class=\"main_view_labels\">\n";
  
  $html .= "<thead>\n";
  $html .= "<tr><th></th></tr>\n";
  $html .= "</thead>\n";
  
  $html .= "<tbody>\n";
  
  
  foreach ($map as $room_id => $row)
  {
    // The first time through get the room names
    if (!isset($room_names))
    {
      $room_names = get_room_names(get_area($room_id));
    }

    $html.= "<tr><td><a href=\"\">" . htmlspecialchars($room_names[$room_id]) . "</a></td></tr>\n";
  }
  $html .= "</tbody>\n";
  
  $html .= "</table>\n";
  
  return $html;
}


function get_row_data_table($map)
{
  // Some browsers cannot handle very large colspans, anmd so we split large colspans
  // up into chunks that they can handle.   For example Firefox will not handle colspans
  // longer than 1000.  See https://bugzilla.mozilla.org/show_bug.cgi?id=321474.  Chrome
  // seems to cope OK, but to keep things simple we'll just chunkify colspans for all
  // browsers.  We assign a class to the first chunk and a different class to subsequent
  // chunks, so that we can use CSS to make it look as though it's all one booking.  The
  // only time this won't work perfectly is if the booking name exceeds the size of the
  // first chunk and so the overflow will be hidden.  But if we keep the chunk size as
  // large as possible this isn't going to happen very often.
  $max_colspan_size = 1000;
  
  $html = '';
  
  $html .= "<table class=\"main_view_data\">\n";

  $html .= "<thead>\n";
  $html .= "<tr>\n";
  $n_cols = count(current($map));
  $column_width = number_format(100/$n_cols, 6);
  for ($i=0; $i<$n_cols; $i++)
  {
    $html .= "<th style=\"max-width: $column_width%\"></th>\n";
  }
  $html .= "</tr>\n";  
  $html .= "</thead>\n";
  
  $html .= "<tbody>\n";
  
  foreach ($map as $room_id => $row)
  {
    $html .= "<tr>\n";
    $last_id = null;
    
    // Cycle through the slots
    while (($data = current($row)) !== false)
    {
      // No booking in this slot
      if (count($data) == 0)
      {
        if (isset($last_id))
        {
          // The booking has come to an end, so write it out
          $last_id = null;
          $html .= get_cell_html($content, $class, $n_slots);
          prev($row);
        }
        else
        {
          // This is an empty slot.  We need the non-breaking space to give the
          // cell height.
          $content = "<a href=\"\">&nbsp;</a>\n";  // JUST FOR NOW - TO DO
          $html .= get_cell_html($content, 'new');
        }
      }
      
      // One booking in this slot
      elseif (count($data) == 1)
      {
        $this_id = $data[0]['id'];
        if (!isset($last_id))
        {
          // Start of a new booking
          $last_id = $this_id;
          $type = $data[0]['type'];
          $class = $type;
          $is_continuation = false;
          $content = "<a href=\"\">" . htmlspecialchars($data[0]['name']) . "</a>\n"; // JUST FOR NOW - TO DO
          $n_slots = 1;
        }
        elseif ($this_id == $last_id)
        {
          // Still in the same booking
          $n_slots++;
          // Check to see whether we've reached the maximum colspan size and whether the next
          // slot is also part of this booking.  If so, write it out and start a new colspan.
          if ($n_slots >= $max_colspan_size)
          {
            $data = next($row);
            if (($data !== false) && (count($data) == 1) && ($data[0]['id'] == $this_id))
            {
              // The next slot is part of the same booking, so write it out and start
              // a new colspan.
              if (!$is_continuation)
              {
                $class = "$type first_part";
              }
              $html .= get_cell_html($content, $class, $n_slots);
              $is_continuation = true;
              $class = "$type continuation";
              $n_slots = 1;
            }
            else
            {
              // It's not part of the same booking, so just go back one slot and the booking will
              // get written out naturally on the next iteration.
              prev($row);
            }
          
          }
        }
        else
        {
          // The booking has come to an end, so write it out
          $last_id = null;
          $html .= get_cell_html($content, $class, $n_slots);
          prev($row);
        }
      }
      
      // More than one booking in this slot.   This will happen if the resolution is changed
      // or the slots are shifted.
      else
      {
        trigger_error("To do");
      }
      $data = next($row);
      if (($data === false) && isset($last_id))
      {
        // We're at the end of the row and there's a booking to write out
        $html .= get_cell_html($content, $class, $n_slots);
        break;
      }
    }
    
    $html .= "</tr>\n";
  }
  
  $html .= "</tbody>\n";
  $html .= "</table>\n";
  
  return $html;
}


function get_grid($area, $interval)
{
  $html = '';
  
  $html .= "<table class=\"grid\">\n";
  
  // First get all the slots in this interval
  $date = new DateTime();
  $date->setTimestamp($interval['start']);
  
  $days = array();
  
  do {
    $j = $date->format('j');  // day, no leading zero
    $n = $date->format('n');  // month, no leading zero
    $Y = $date->format('Y');  // year, four digits
    
    $days[] = get_slot_starts($n, $j, $Y);
    $date->modify('+1 day');
  } while (get_end_last_slot($n, $j, $Y) < $interval['end']);

  // Get the number of rooms
  $rooms = get_rooms($area);
  $n_rooms = count($rooms);
  
  for ($i=0; $i<$n_rooms; $i++)
  {
    $html .= "<tr>\n";
    foreach ($days as $day)
    {
      $first_slot = true;
      foreach ($day as $slot)
      {
        $html .= "<td";
        if ($first_slot)
        {
          $html .= " class=\"first_slot_of_day\"";
          $first_slot = false;
        }
        $html .= "><span>&nbsp;</span></td>\n";
      }
    }
    $html .= "</tr>\n";
  }
  
  $html .= "</table>\n";
  
  return $html;
}


function get_table($map, $area, $interval, $view)
{
  $html = '';
  
  // We use nested tables so that we can get set the column width exactly for
  // the main data.
  $html .= "<table class=\"main_view $view\">\n";
  $html .= "<tr>\n";
  $html .= "<td>" . get_row_labels_table($map) . "</td>\n";
  $html .= "<td class=\"data\">" . get_row_data_table($map) . get_grid($area, $interval) . " </td>\n";
  $html .= "</tr>\n";
  $html .= "</table>\n";
  
  return $html;
}


if (!checkAuthorised())
{
  exit;
}

$view = get_form_var('view', 'string', $default_view);
$interval = get_interval($view, $month, $day, $year);
$entries = get_entries_by_area($area, $interval['start'], $interval['end']);
$map = get_map($area, $entries, $interval);

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : null);

echo "<div id=\"dwm_header\" class=\"screenonly\">\n";

// Show all available areas
echo make_area_select_html('index.php', $area, $year, $month, $day);

// Draw the three month calendars
if (!$display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'day');
}

echo "</div>\n";
echo get_table($map, $area, $interval, $view);
output_trailer();
