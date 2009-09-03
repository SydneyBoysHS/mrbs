<?php

/*
 *
 * File level doc block. To be completed.
 *
 */


/*
 * mrbs_acl_api Custom Extended API Class
 *
 * @author Paul van der Westhuizen <proj_admin@users.sourceforge.net>
 *
 */

class MRBS_acl_api extends gacl_api {

  function addObject($section_value, $object_id, $name, $object_type)
  {
    $group_id = $this->get_group_id("all-$section_value",'',$object_type);
    if ($obj_id = $this->add_object($section_value,$name,$object_id,0,0,$object_type))
      $this->add_group_object($group_id,$section_value,$object_id,$object_type);
  }

  function delObject($section_value, $object_value, $object_type)
  {
    $group_id = $this->get_group_id("all-$section_value",'',$object_type);
    $object_id = $this->get_object_id($section_value, $object_value, $object_type);

    // Delete object from group first
    $this->del_group_object($group_id, $section_value, $object_value, $object_type);

    // Then delete object
    if ($this->del_object($object_id, $object_type))
      return TRUE;
    else
      return FALSE;
  }

}

/*
get_group_id('all-areas','','AXO');

get_object_id($section_value, $value, $object_type=NULL)

add_object($section_value, $name, $value=0, $order=0, $hidden=0, $object_type=NULL) -> Returns ID of new object if successful or false.
edit_object($object_id, $section_value, $name, $value=0, $order=0, $hidden=0, $object_type=NULL) -> Returns TRUE or FALSE
del_object($object_id, $object_type=NULL, $erase=FALSE) -> Returns TRUE or FALSE

add_group_object($group_id, $object_section_value, $object_value, $group_type='ARO') -> Returns TRUE or FALSE
del_group_object($group_id, $object_section_value, $object_value, $group_type='ARO') -> Returns TRUE or FALSE
*/

?>
