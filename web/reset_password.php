<?php
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementP;
use MRBS\Form\ElementSpan;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\Form;

require "defaultincludes.inc";


function generate_request_reset_form()
{
  $can_reset_by_email = auth()->canResetByEmail();

  $form = new Form();
  $form->setAttributes(array(
      'class'  => 'standard',
      'id'     => 'lost_password',
      'method' => 'post',
      'action' => multisite('reset_password_handler.php')
    ));

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(\MRBS\get_vocab('password_reset'));

  $field = new FieldDiv();
  $p = new ElementP();
  $text = ($can_reset_by_email) ? get_vocab('enter_username_or_email') : get_vocab('enter_username');
  $text .= ' ' . get_vocab('will_be_sent_instructions');
  $p->setText($text);
  $field->addControlElement($p);

  $fieldset->addElement($field);

  $tag = ($can_reset_by_email) ? 'username_or_email' : 'users.name';
  $placeholder = \MRBS\get_vocab($tag);

  $field = new FieldInputText();
  $field->setLabel(\MRBS\get_vocab('user'))
        ->setLabelAttributes(array('title' => $placeholder))
        ->setControlAttributes(array('id'           => 'username',
                                     'name'         => 'username',
                                     'placeholder'  => $placeholder,
                                     'required'     => true,
                                     'autofocus'    => true,
                                     'autocomplete' => 'username'));
  $fieldset->addElement($field);

  $form->addElement($fieldset);

  // The submit button
  $fieldset = new ElementFieldset();
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => \MRBS\get_vocab('get_new_password')));
  $fieldset->addElement($field);

  $form->addElement($fieldset);

  $form->render();
}


// If we haven't got the ability to reset passwords then get out of here
if (!auth()->canResetPassword())
{
  location_header('index.php');
}

// Check the user is authorised for this page
checkAuthorised(this_page());

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

generate_request_reset_form();

print_footer();
