<?php
/*****************************************************************************\
*									      *
*   File name       edit_users.php					      *
*									      *
*   Description	    Edit the user database                                    *
*									      *
*   Notes	    Automatically creates the database if it's not present.   *
*									      *
*		    Designed to be easily extensible:                         *
*                   Adding more fields for each user does not require         *
*                    modifying the editor code. Only to add the fields in     *
*                    the database creation code.                              *
*									      *
*		    To do:						      *
*			- Localisability                                      *
*									      *
*   History								      *
*    2003/12/29 JFL Created this file					      *
*									      *
\*****************************************************************************/

// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
require_once("database.inc.php");
include "$dbsys.inc";
include "mrbs_auth.inc";

/*---------------------------------------------------------------------------*\
|                     Create the users database if needed                     |
\*---------------------------------------------------------------------------*/

//   If the table does not exist, then create it

$nusers = $mdb->listTables();
if (MDB::isError($nusers))
{
    fatal_error(1, $nusers->getMessage() . "<br>" . $nusers->getUserInfo());
}
if (!in_array("$tbl_users", $nusers))
{   /*
       The first three fields are required (id, name, password). Don't remove
       or reorder.
       The following fields are application-specific. However only int and
       varchar are editable.
    */
    $fields = array(
   		'id'		=> array(
			'type'		=> 'integer',
           	'notnull' 	=> 1,
            'default' 	=> 0
   	        ),
       	'name'		=> array(
       		'type'		=> 'text',
            'length' 	=> 30
   	        ),
       	'password'	=> array(
       		'type'		=> 'text',
            'length'	=> 30
            ),
   	    'email'		=> array(
       		'type'		=> 'text',
           	'length'	=> 75
            )
   	);
    $r = $mdb->createTable($tbl_users, $fields);
    /* No need to localize the following error messages: Only the admin
       running this for the first time would see it.
    */
    if (MDB::isError($r))
    {
        fatal_error(1, "<p>Error creating the $tbl_users table.<br>\n"
        	. $r->getMessage() . "<br>" . $r->getUserInfo());
    }
    $properties = array(
        'FIELDS' => array(
        	'id'	=> array()
            ),
        'unique' => 1
    );
    $r = $mdb->createIndex($tbl_users, "${tbl_users}_pkey", $properties);
    if (MDB::isError($r))
    {
        fatal_error(1, "<p>Error creating the $tbl_users table indexes.<br>\n"
        	. $r->getMessage() . "<br>" . $r->getUserInfo());
    }
    $r = $mdb->createSequence("${tbl_users}_id");
    if (MDB::isError($r))
    {
        fatal_error(1, "<p>Error creating the $tbl_users sequence.<br>\n"
        	. $r->getMessage() . "<br>" . $r->getUserInfo());
    }
    $nusers = 0;
}

/* Get the list of fields actually in the table. (Allows the addition of new fields later on) */
$field_name = $mdb->listTableFields($tbl_users);
if (MDB::isError($field_name))
{
    fatal_error(1, $field_name->getMessage() . "<br>" . $field_name->getUserInfo());
}
$nfields = sizeof($field_name);
for ($i=0; $i<$nfields; $i++)
{
    $types = $mdb->getTableFieldDefinition($tbl_users, $field_name[$i]);
    if (MDB::isError($types))
	{
    fatal_error(1, $types->getMessage() . "<br>" . $types->getUserInfo());
	}
    $field_type[$i] = $types[0][0]['type'];
}

/* Get localized field name */
function get_loc_field_name($i)
    {
    global $field_name, $vocab;

    $name = $field_name[$i];  // $name = "name", "password", ...
    // Search for indexes "user_name", "user_password", etc, in the localization array.
    if (isset($vocab["user_".$name])) return get_vocab("user_".$name);
    // If there is no entry (likely if user-defined fields have been added), return itself.
    return $name;
    }

/*---------------------------------------------------------------------------*\
|                         Authentify the current user                         |
\*---------------------------------------------------------------------------*/

if ($nusers > 0)
    {
    $user = getUserName();
    $level = authGetUserLevel($user, $auth["admin"]);
    // Do not allow unidentified people to browse the list.
    if(!getAuthorised(1))
        {
        showAccessDenied($day, $month, $year, $area);
        exit;
        }
    }
else /* We've just created the table. Assume the person doing this IS the administrator. */
    {
    $user = "administrator";
    $level = 2;
    }

/*---------------------------------------------------------------------------*\
|             Edit a given entry - 1st phase: Get the user input.             |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ( ($Action == "Edit") or ($Action == "Add") ))
    {
    if ($Id >= 0) /* -1 for new users, or >=0 for existing ones */
    {
        $data = $mdb->queryRow(
        	"SELECT * FROM $tbl_users WHERE id=$Id", $field_type);
        if (MDB::isError($data))
        {
    		fatal_error(1, $data->getMessage() . "<br>" . $data->getUserInfo());
    	}
    }
    if (($Id == -1) || (!$data)) /* Set blank data for undefined entries */
    	{
    	for ($i=0; $i<$nfields; $i++) $data[$i] = "";
    	}

    /* First make sure the user is authorized */
    if (!getWritable($data[1], $user))
        {
        showAccessDenied(0, 0, 0, "");
        exit();
        }

    print_header(0, 0, 0, 0);

    if ($Action == "Edit")
    {
        print "<h2>" . get_vocab("edit_user") . "</h2>\n";
    }
    else
    {
        print "<h2>" . get_vocab("add_new_user") . "</h2>\n";
    }

    if (($Id >= 0) && ($level == 2)) /* Administrators get the right to delete users */
        {
        print "<p><form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "\t<input type=hidden name=Action value=Delete />\n";
        print "\t<input type=hidden name=Id value=$Id />\n";
        print "\t<input style=\"margin:0\" type=submit value=\"" . get_vocab("delete_user") . "\" />\n";
        print "</form></p>\n";
        }

    print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
    print "  <table>\n";

    for ($i=0; $i<$nfields; $i++)
        {
        /* The ID field cannot change; The password field must not be shown. */
        if ($field_name[$i] == "id")
            {
            print "    <input type=hidden name=Id value=$Id />\n";
            continue;
            }
        if ($field_name[$i] == "password")
            {
            print "    <input type=hidden name=\"Field[$i]\" value=\"".
                        htmlspecialchars($data[$i])."\" />\n";
            continue;
            }
        print "    <tr>\n";
        print "      <td align=right valign=bottom>" . get_loc_field_name($i) . "</td>\n";
        print "      <td><input type=text name=\"".htmlspecialchars("Field[$i]").
                          "\" value=\"".htmlspecialchars($data[$i])."\" /></td>\n";
        // Display message about invalid email
        (!isset($invalid_email)) ? $invalid_email = '' : '' ;
        if ( ($field_name[$i] == "email") && (1 == $invalid_email) )
        {
            print ("<td><STRONG>" . get_vocab('invalid_email') . "<STRONG></td>\n");
        }
        print "    </tr>\n";
        }
    print "  </table>\n";

    print " <br>" . get_vocab("password_twice") . "...<br><br>\n";
    print "  <table>\n";
    for ($i=0; $i<2; $i++)
        {
        print "    <tr>\n";
        print "      <td align=right valign=center>" . get_vocab("user_password") . "</td>\n";
        print "      <td><input type=password name=password$i value=\"\" /></td>\n";
        print "    </tr>\n";
        }
    print "  </table>\n";
/*    print "  <input type=hidden name=Id value=\"$this_id\" /> <br />\n"; */
    print "  <input type=hidden name=Action value=Update /> <br />\n";
    print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
    print "</form>\n</body>\n</html>\n";

    exit();
    }

/*---------------------------------------------------------------------------*\
|             Edit a given entry - 2nd phase: Update the database.            |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ($Action == "Update"))
{
    /* To do: Add JavaScript to verify passwords _before_ sending the form here */
    if ($password0 != $password1)
        {
	print_header(0, 0, 0, "");

        print get_vocab("passwords_not_eq") . "<br>\n";

        print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
        print "</form>\n</body>\n</html>\n";

        exit();
        }
    //
    // Verify email adresses
    include_once 'Mail/RFC822.php';
    (!isset($Field[3])) ? $Field[3] = '': '';
    $emails = explode(',', $Field[3]);
    $valid_email = new Mail_RFC822();
    foreach ($emails as $email)
    {
        // if no email address is entered, this is OK, even if isValidInetAddress
        // does not return TRUE
        if ( !$valid_email->isValidInetAddress($email, $strict = FALSE)
            && ('' != $Field[3]) )
        {
            // Now display this form again with an error message
            Header("Location: edit_users.php?Action=Edit&Id=$Id&invalid_email=1");
            exit;
        }
    }
    //
    if ($Id >= 0)
    {
        // This is a REPLACE
        $replaced_fields = array();
        for ($i=0; $i<$nfields; $i++)
        {
            if ($field_name[$i]=="id") $Field[$i] = $Id;
            if ($field_name[$i]=="name") $Field[$i] = strtolower($Field[$i]);
            if (($field_name[$i]=="password") && ($password0!="")) $Field[$i]=md5($password0);
            if ((stristr($field_type[$i], "integer")) && ($Field[$i] == "")) $Field[$i] = "0";

        	$replaced_fields[$field_name[$i]] = array(
            	'Value' => $Field[$i],
                'Type'  => $field_type[$i]
                	);
            if ($field_name[$i]=="id")
            {
                $replaced_fields[$field_name[$i]]['Key'] = 1;
			}
        }
        $r = $mdb->replace($tbl_users, $replaced_fields);
        if (MDB::isError($r))
		{
    		fatal_error(1, $r->getMessage() . "<br>" . $r->getUserInfo());
		}
    }
    else
    {
        $operation = "INSERT INTO $tbl_users VALUES (";
        $Id = $mdb->nextId("${tbl_users}_id");
        if (MDB::isError($Id))
        {
            fatal_error(1, $Id->getMessage() . "<br>" . $Id->getUserInfo());
        }
    	for ($i=0; $i<$nfields; $i++)
        {
        	if ($field_name[$i]=="id") $Field[$i] = $Id;
        	if ($field_name[$i]=="name") $Field[$i] = strtolower($Field[$i]);
        	if (($field_name[$i]=="password") && ($password0!="")) $Field[$i]=md5($password0);
        	/* print "$field_name[$i] = $Field[$i]<br>"; */
        	if ($i > 0) $operation = $operation . ", ";
        	//if (stristr($field_type[$i], "char")) $operation .= "'";
        	if ((stristr($field_type[$i], "integer")) && ($Field[$i] == "")) $Field[$i] = "0";
        	//$operation = $operation . $Field[$i];
        	if (stristr($field_type[$i], "text"))
            {
            	$operation .= $mdb->getTextValue($Field[$i]);
            }
            else
            {
            	$operation .= $Field[$i];
            }
    	}
    	$operation = $operation . ");";
        $r = $mdb->query($operation);
        if (MDB::isError($r))
        {
            fatal_error(1, $r->getMessage() . "<br>" . $r->getUserInfo());
        }
    }

    /* print $operation . "<br>\n"; */
    if (MDB::isError($r))
        {
    print_header(0, 0, 0, "");

    // This is unlikely to happen in normal  operation. Do not translate.
        print "Error updating the mrbs_users table.<br>\n";
        print $r->getMessage() . "<br>" . $r->getUserInfo() . "<br>\n";

        print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
        print "</form>\n</body>\n</html>\n";

        exit();
        }
    /* print "Database updated successfully.<br><br>\n"; */
    /* Success. Do not display a message. Simply fall through into the list display. */
    }

/*---------------------------------------------------------------------------*\
|                                Delete a user                                |
\*---------------------------------------------------------------------------*/

if (isset($Action) && ($Action == "Delete"))
    {
    if ($level < 2)
    	{
        showAccessDenied(0, 0, 0, "");
        exit();
        }

    $r = $mdb->query("DELETE FROM $tbl_users WHERE id=$Id;");
    if (MDB::isError($r))
        {
	print_header(0, 0, 0, "");

	// This is unlikely to happen in normal  operation. Do not translate.
        print "Error deleting entry $Id from the $tbl_users table.<br>\n";
        print $r->getMessage() . "<br>" . $r->getUserInfo() . "<br>\n";

        print "<form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "  <input type=submit value=\" " . get_vocab("ok") . " \" /> <br />\n";
        print "</form>\n</body>\n</html>\n";

        exit();
        }
    /* print "Database updated successfully.<br><br>\n"; */
    /* Success. Do not display a message. Simply fall through into the list display. */
    }

/*---------------------------------------------------------------------------*\
|                          Display the list of users                          |
\*---------------------------------------------------------------------------*/

/* Print the standard MRBS header */

print_header(0, 0, 0, "");

print "<h2>" . get_vocab("user_list") . "</h2>\n";

if ($level == 2) /* Administrators get the right to add new users */
    {
    print "<p><form method=post action=\"" . basename($PHP_SELF) . "\">\n";
    print "\t<input type=hidden name=Action value=Add />\n";
    print "\t<input type=hidden name=Id value=\"-1\" />\n";
    print "\t<input style=\"margin:0\" type=submit value=\"" . get_vocab("add_new_user") . "\" />\n";
    print "</form></p>\n";
    }

$list = $mdb->query("SELECT * FROM $tbl_users ORDER BY name", $field_type);
if (MDB::isError($list))
{
    fatal_error(0, $list->getMessage() . "<br>" . $list->getUserInfo());
}
print "<table border=1>\n";
print "<tr>";
// The first 2 columns are the user rights and uaser name.
print "<th>" . get_vocab("rights") . "</th><th>" . get_vocab("user_name") . "</th>";
// The remaining columns are all the columns from the database, past the initial 3 (id, name, password).
for ($i=3; $i<$nfields; $i++) print "<th>" . get_loc_field_name($i) . "</th>";
print "<th>" . get_vocab("action") . "</th>";
print "</tr>\n";
$i = 0; 
while ($line = $mdb->fetchInto($list))
    {
    print "\t<tr>\n";
    $j = -1;
    $this_id = 0;
    foreach ($line as $col_value) 
        {
        $j += 1;
        if ($j == 0)	/* The 1st data is the ID. */
            {		/* Don't display it, but remember it. */
            $this_id = $col_value;
            continue;
            }
        if ($j == 1)	/* The 2nd data is the name. */
            {		/* Use it to tell if it's a user or an admin */
            $name = $col_value;
            switch (authGetUserLevel($name, $auth["admin"]))
                {
                case 1: $right = get_vocab("user"); break;
                case 2: $right = get_vocab("administrator"); break; // MRBS admin
                case 3: $right = get_vocab("administrator"); break; // Reserved for future user admin.
                default: $right = get_vocab("unknown"); break;
                }
            print "\t\t<td>$right</td>\n";
            /* Fall through to display the name */
            }
        if ($j == 2)	/* The 3rd data is the password, which we must not display. */
            {
            continue;
            }
        /* Display the data, if any. */
        if ($col_value == "") $col_value = "&nbsp;"; // IE doesn't print a frame around void data.
        print "\t\t<td>$col_value</td>\n";
        }
    print "\t\t<td>\n";
    if (getWritable($name, $user)) /* If the logged-on user has the right to edit this entry */
    	{
        print "\t\t    <form method=post action=\"" . basename($PHP_SELF) . "\">\n";
        print "\t\t\t<input type=hidden name=Action value=Edit />\n";
        print "\t\t\t<input type=hidden name=Id value=\"$this_id\" />\n";
        print "\t\t\t<input style=\"margin:0\" type=submit value=\"" . get_vocab("edit") . "\" />\n";
        print "\t\t    </form>\n";
        }
    else
    	{
    	print "\t\t\t&nbsp;\n";
    	}
    print "\t\t</td>\n";
    print "\t</tr>\n";
    }
    $mdb->freeResult($list);
print "</table>\n";

include "trailer.inc";
?>
