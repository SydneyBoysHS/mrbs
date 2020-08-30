<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementSelect;

require "defaultincludes.inc";
require_once "functions_table.inc";


// Display the entry-type color key.
function get_color_key()
{
  global $booking_types;

  $html = '';

  // No point in showing the color key if we aren't using entry types.  (Note:  count()
  // returns 0 if its parameter is not set).
  if (isset($booking_types) && (count($booking_types) > 1))
  {
    $html .= "<div class=\"color_key js_hidden\">\n";

    foreach ($booking_types as $key)
    {
      $html .= "<div class=\"$key\">" . get_type_vocab($key) . "</div>\n";
    }

    $html .= "</div>\n";
  }

  return $html;
}


// generates some html that can be used to select which area should be
// displayed.
function make_area_select_html($view, $year, $month, $day, $current)
{
  global $multisite, $site;

  $out_html = '';

  $areas = get_area_names();

  // Only show the areas if there are more than one of them, otherwise
  // there's no point
  if (count($areas) > 1)
  {
    $page_date = format_iso_date($year, $month, $day);

    $form = new Form();

    $form->setAttributes(array('class'  => 'areaChangeForm',
                               'method' => 'get',
                               'action' => multisite(this_page())));

    $form->addHiddenInputs(array('view'      => $view,
                                 'page_date' => $page_date));

    if ($multisite && isset($site) && ($site !== ''))
    {
      $form->addHiddenInput('site', $site);
    }

    $select = new ElementSelect();
    $select->setAttributes(array('class'      => 'room_area_select',
                                 'name'       => 'area',
                                 'aria-label' => get_vocab('select_area'),
                                 'onchange'   => 'this.form.submit()'))
           ->addSelectOptions($areas, $current, true);
    $form->addElement($select);

    // Note:  the submit button will not be displayed if JavaScript is enabled
    $submit = new ElementInputSubmit();
    $submit->setAttributes(array('class' => 'js_none',
                                 'value' => get_vocab('change')));
    $form->addElement($submit);

    $out_html .= $form->toHTML();
  }

  return $out_html;
} // end make_area_select_html


function make_room_select_html ($view, $view_all, $year, $month, $day, $area, $current)
{
  global $multisite, $site;

  $out_html = '';

  $rooms = get_room_names($area);
  $n_rooms = count($rooms);

  if ($n_rooms > 0)
  {
    $page_date = format_iso_date($year, $month, $day);
    $options = $rooms;

    // If we are in the week or month views and there is more than one room, then add the 'all'
    // option to the room select, which allows the user to display all rooms in the view.
    // And if we are viewing all the rooms then make sure the current room is negative.
    // (The room select uses a negative value of $room to signify that we want to view all
    // rooms in an area.   The absolute value of $room is the current room.)
    if (in_array($view, array('week', 'month')) && ($n_rooms > 1))
    {
      $all = -abs($current);
      if ($view_all)
      {
        $current = -abs($current);
      }
      $options = array($all => get_vocab('all')) + $options;
    }

    $form = new Form();

    $form->setAttributes(array('class'  => 'roomChangeForm',
                               'method' => 'get',
                               'action' => multisite(this_page())));

    $form->addHiddenInputs(array('view'      => $view,
                                 'view_all'  => 0,
                                 'page_date' => $page_date,
                                 'area'      => $area));

    if ($multisite && isset($site) && ($site !== ''))
    {
      $form->addHiddenInput('site', $site);
    }

    $select = new ElementSelect();
    $select->setAttributes(array('class'      => 'room_area_select',
                                 'name'       => 'room',
                                 'aria-label' => get_vocab('select_room'),
                                 'onchange'   => 'this.form.submit()'))
           ->addSelectOptions($options, $current, true);
    $form->addElement($select);

    // Note:  the submit button will not be displayed if JavaScript is enabled
    $submit = new ElementInputSubmit();
    $submit->setAttributes(array('class' => 'js_none',
                                 'value' => get_vocab('change')));
    $form->addElement($submit);

    $out_html .= $form->toHTML();
  }

  return $out_html;
} // end make_room_select_html



// Gets the link to the next/previous day/week/month
function get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, $next=false)
{
  switch ($view)
  {
    case 'day':
      // find the adjacent non-hidden day
      $d = $day;
      do
      {
        $d += ($next) ? 1 : -1;
        $time = mktime(12, 0, 0, $month, $d, $year);
      }
      while (is_hidden_day(date('w', $time)) && (abs($d - $day) < DAYS_PER_WEEK));  // break the loop if all days are hidden
      break;
    case 'week':
      $time = mktime(12, 0, 0, $month, $day + (($next) ? DAYS_PER_WEEK : -DAYS_PER_WEEK), $year);
      break;
    case 'month':
      $time = mktime(12, 0, 0, $month + (($next) ? 1 : -1), 1, $year);
      // Keep the day information, but make sure it's a valid day in the new month
      $d = min($day, date('t', $time));
      $time = mktime(12, 0, 0, $month + (($next) ? 1 : -1), $d, $year);
      break;
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $date = getdate($time);

  $vars = array('view'      => $view,
                'view_all'  => $view_all,
                'page_date' => format_iso_date($date['year'], $date['mon'], $date['mday']),
                'area'      => $area,
                'room'      => $room);

  return 'index.php?' . http_build_query($vars, '', '&');
}


// Gets the link for today
function get_today_link($view, $view_all, $area, $room)
{
  $date = getdate();

  $vars = array('view'      => $view,
                'view_all'  => $view_all,
                'page_date' => format_iso_date($date['year'], $date['mon'], $date['mday']),
                'area'      => $area,
                'room'      => $room);

  return 'index.php?' . http_build_query($vars, '', '&');
}


function get_location_nav($view, $view_all, $year, $month, $day, $area, $room)
{
  $html = '';

  $html .= "<nav class=\"location js_hidden\">\n";  // JavaScript will show it
  $html .= make_area_select_html($view, $year, $month, $day, $area);

  if ($view !== 'day')
  {
    $html .= make_room_select_html($view, $view_all, $year, $month, $day, $area, $room);
  }

  $html .= "</nav>\n";

  return $html;
}


function get_view_nav($current_view, $view_all, $year, $month, $day, $area, $room)
{
  $html = '';

  $html .= '<nav class="view">';
  $html .= '<div class="container">';  // helps the CSS

  $views = array('day' => 'nav_day',
                 'week' => 'nav_week',
                 'month' => 'nav_month');

  foreach ($views as $view => $token)
  {
    $this_view_all = (isset($view_all)) ? $view_all : 1;

    $vars = array('view'      => $view,
                  'view_all'  => $this_view_all,
                  'page_date' => format_iso_date($year, $month, $day),
                  'area'      => $area,
                  'room'      => $room);

    $query = http_build_query($vars, '', '&');
    $href = multisite("index.php?$query");
    $html .= '<a';
    $html .= ($view == $current_view) ? ' class="selected"' : '';
    $html .= ' href="' . htmlspecialchars($href) . '">' . htmlspecialchars(get_vocab($token)) . '</a>';
  }

  $html .= '</div>';
  $html .= '</nav>';

  return $html;
}


function get_arrow_nav($view, $view_all, $year, $month, $day, $area, $room)
{
  $html = '';

  switch ($view)
  {
    case 'day':
      $title_prev = get_vocab('daybefore');
      $title_this = get_vocab('gototoday');
      $title_next = get_vocab('dayafter');
      break;
    case 'week':
      $title_prev = get_vocab('weekbefore');
      $title_this = get_vocab('gotothisweek');
      $title_next = get_vocab('weekafter');
      break;
    case 'month':
      $title_prev = get_vocab('monthbefore');
      $title_this = get_vocab('gotothismonth');
      $title_next = get_vocab('monthafter');
      break;
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $title_prev = htmlspecialchars($title_prev);
  $title_next = htmlspecialchars($title_next);

  $link_prev = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, false);
  $link_today = get_today_link($view, $view_all, $area, $room);
  $link_next = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, true);

  $link_prev = multisite($link_prev);
  $link_today = multisite($link_today);
  $link_next = multisite($link_next);

  $html .= "<nav class=\"arrow\">\n";
  $html .= "<a class=\"prev\" title=\"$title_prev\" aria-label=\"$title_prev\" href=\"" . htmlspecialchars($link_prev) . "\"></a>";  // Content will be filled in by CSS
  $html .= "<a title= \"$title_this\" aria-label=\"$title_this\" href=\"" . htmlspecialchars($link_today) . "\">" . get_vocab('today') . "</a>";
  $html .= "<a class=\"next\" title=\"$title_next\" aria-label=\"$title_next\" href=\"" . htmlspecialchars($link_next) . "\"></a>";  // Content will be filled in by CSS
  $html .= "</nav>";

  return $html;
}


function get_calendar_nav($view, $view_all, $year, $month, $day, $area, $room, $hidden=false)
{
  $html = '';

  $html .= "<nav class=\"main_calendar" .
           (($hidden) ? ' js_hidden' : '') .
           "\">\n";

  $html .= get_arrow_nav($view, $view_all, $year, $month, $day, $area, $room);
  $html .= get_location_nav($view, $view_all, $year, $month, $day, $area, $room);
  $html .= get_view_nav($view, $view_all, $year, $month, $day, $area, $room);

  $html .= "</nav>\n";

  return $html;
}


function get_date_heading($view, $year, $month, $day)
{
  global $strftime_format, $display_timezone,
         $weekstarts, $mincals_week_numbers;

  $html = '';
  $time = mktime(12, 0, 0, $month, $day, $year);

  $html .= '<h2 class="date">';

  switch ($view)
  {
    case 'day':
      $html .= utf8_strftime($strftime_format['view_day'], $time);
      break;

    case 'week':
      // Display the week number if required, provided the week starts on Monday,
      // otherwise it's spanning two ISO weeks and doesn't make sense.
      if ($mincals_week_numbers && ($weekstarts == 1))
      {
        $html .= '<span class="week_number">' .
                 get_vocab('week_number', date('W', $time)) .
                 '</span>';
      }
      // Then display the actual dates
      $day_of_week = date('w', $time);
      $our_day_of_week = ($day_of_week + DAYS_PER_WEEK - $weekstarts) % DAYS_PER_WEEK;
      $start_of_week = mktime(12, 0, 0, $month, $day - $our_day_of_week, $year);
      $end_of_week = mktime(12, 0, 0, $month, $day + 6 - $our_day_of_week, $year);
      // We have to cater for three possible cases.  For example
      //    Years differ:                   26 Dec 2016 - 1 Jan 2017
      //    Years same, but months differ:  30 Jan - 5 Feb 2017
      //    Years and months the same:      6 - 12 Feb 2017
      if (date('Y', $start_of_week) != date('Y', $end_of_week))
      {
        $start_format = $strftime_format['view_week_start_y'];
      }
      elseif (date('m', $start_of_week) != date('m', $end_of_week))
      {
        $start_format = $strftime_format['view_week_start_m'];
      }
      else
      {
        $start_format = $strftime_format['view_week_start'];
      }
      $html .= utf8_strftime($start_format, $start_of_week) . '-' .
               utf8_strftime($strftime_format['view_week_end'], $end_of_week);
      break;

    case 'month':
      $html .= utf8_strftime($strftime_format['view_month'], $time);
      break;

    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $html .= '</h2>';

  if ($display_timezone)
  {
    $html .= '<span class="timezone">';
    $html .= get_vocab("timezone") . ": " . date('T', $time) . " (UTC" . date('O', $time) . ")";
    $html .= '</span>';
  }

  return $html;
}


// Get non-standard form variables
$refresh = get_form_var('refresh', 'int');
$timetohighlight = get_form_var('timetohighlight', 'int');

// The room select uses a negative value of $room to signify that we want to view all
// rooms in an area.   The absolute value of $room is the current room.
if ($room < 0)
{
  $room = abs($room);
  $view_all = 1;
}

$is_ajax = is_ajax();

// If we're using the 'db' authentication type, check to see if MRBS has just been installed
// and, if so, redirect to the edit_users page so that they can set up users.
if (($auth['type'] == 'db') && (count(auth()->getUsers()) == 0))
{
  location_header('edit_users.php');
}

// Check the user is authorised for this page
if (!checkAuthorised(this_page(), $refresh))
{
  exit;
}

switch ($view)
{
  case 'day':
    $inner_html = day_table_innerhtml($view, $year, $month, $day, $area, $room, $timetohighlight);
    break;
  case 'week':
    $inner_html = week_table_innerhtml($view, $view_all, $year, $month, $day, $area, $room, $timetohighlight);
    break;
  case 'month':
    $inner_html = month_table_innerhtml($view, $view_all, $year, $month, $day, $area, $room);
    break;
  default:
    throw new \Exception("Unknown view '$view'");
    break;
}

if ($refresh)
{
  echo $inner_html;
  exit;
}

// print the page header
$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => isset($room) ? $room : null
  );

print_header($context);

echo "<div class=\"minicalendars\">\n";
echo "</div>\n";

echo "<div class=\"view_container js_hidden\">\n";
echo get_date_heading($view, $year, $month, $day);
echo get_calendar_nav($view, $view_all, $year, $month, $day, $area, $room);

$classes = array('dwm_main');
if ($times_along_top)
{
  $classes[] .= 'times-along-top';
}
if ($view_all && ($view !== 'day'))
{
  $classes[] = 'all_rooms';
}

echo "<div class=\"table_container\">\n";
echo '<table class="' . implode(' ', $classes) . "\" id=\"${view}_main\" data-resolution=\"$resolution\">\n";
echo $inner_html;
echo "</table>\n";
echo "</div>\n";

// The bottom navigation bar is controlled by JavaScript
echo get_calendar_nav($view, $view_all, $year, $month, $day, $area, $room, true);

echo get_color_key();
echo "</div>\n";

print_footer();
