<?php
namespace MRBS;

require "defaultincludes.inc";


// Gets the booking interval (start first slot to end last slot)
// for a given view.   Returns an array indexed by 'start' and 'end'.
function get_interval($view, $month, $day, $year)
{
  global $weekstarts;
  
  $result = array();
  
  switch ($view)
  {
    case 'week':
      $day_of_week = date('w', mktime(12, 0, 0, $month, $day, $year));
      $days_after_start_of_week = ($day_of_week + DAYS_PER_WEEK - $weekstarts) % 7;
      $result['start'] = get_start_first_slot($month, $day - $days_after_start_of_week, $year);
      $result['end'] = get_end_last_slot($month, $day + (DAYS_PER_WEEK - 1) - $days_after_start_of_week, $year);
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
          $map[$room_id][$slot][] = $entry['id'];
          $slot = next($slots);
        } while (($slot !== false) && ($entry['end_time'] > ($slot + $resolution)));
        reset($slots);
        break;
      }
      $slot = next($slots);
    }
    reset($slots);
  }

  return $map;
}


function get_table($map)
{
  $html = '';
  
  $html .= "<table>\n";
  
  foreach ($map as $room_id => $row)
  {
    $html .= "<tr>\n";
    $html .= "<td>$room_id</td>\n";
    foreach ($row as $data)
    {
      $html .= "<td>";
      if (count($data) == 1)
      {
        $html .= $data[0];
      }
      elseif (count($data) > 1)
      {
        trigger_error("To do");
      }
      $html .= "</td>\n";
    }
    
    $html .= "</tr>\n";
  }
  
  $html .= "</table>\n";
  
  return $html;
}


if (!checkAuthorised())
{
  exit;
}

$view = 'week';
$interval = get_interval($view, $month, $day, $year);
$entries = get_entries_by_area($area, $interval['start'], $interval['end']);
$map = get_map($area, $entries, $interval);
echo get_table($map);
