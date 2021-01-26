<?php
namespace MRBS;


class Room extends Location
{
  const TABLE_NAME = 'room';

  protected static $unique_columns = array('room_name', 'area_id');


  public function __construct($room_name=null, $area_id=null)
  {
    parent::__construct();
    $this->room_name = $room_name;
    $this->sort_key = $room_name;
    $this->area_id = $area_id;
    $this->disabled = false;
    $this->area_disabled = false;
  }


  public static function getByName($name)
  {
    return self::getByColumn('room_name', $name);
  }


  // Checks if the room is disabled.  A room is disabled if either it or
  // its area has been disabled.
  public function isDisabled()
  {
    return ($this->disabled || $this->area_disabled);
  }


  // Determines whether the room is writable by the currently logged in user
  public function isWritable()
  {
    if (!isset($this->is_writable))
    {
      $this->is_writable = $this->isAble(RoomRule::WRITE,
                                         session()->getCurrentUser());
    }

    return $this->is_writable;
  }


  // Determines whether the currently logged in user is a booking admin for this room
  public function isBookAdmin()
  {
    if (!isset($this->is_book_admin))
    {
      $this->is_book_admin = $this->isAble(RoomRule::ALL,
                                           session()->getCurrentUser());
    }

    return $this->is_book_admin;
  }


  // Function to decode any columns that are stored encoded in the database
  protected static function onRead(array $row)
  {
    if (isset($row['invalid_types']))
    {
      $row['invalid_types'] = json_decode($row['invalid_types']);
    }

    return $row;
  }


  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite(array $row)
  {
    if (isset($row['invalid_types']))
    {
      $row['invalid_types'] = json_encode($row['invalid_types']);
    }

    return $row;
  }


  public function getRules(array $role_ids)
  {
    return RoomRule::getRulesByRoles($role_ids, $this->id);
  }


  // Gets the area_id for a room with id $id
  public static function getAreaId($id)
  {
    $sql = "SELECT area_id
              FROM " . _tbl(self::TABLE_NAME) . "
             WHERE id=?
             LIMIT 1";

    $area_id = (int) db()->query1($sql, array($id));

    return ($area_id < 0) ? null : $area_id;
  }


  // For efficiency we get some information about the area at the same time.
  protected static function getByColumn($column, $value)
  {
    $sql = "SELECT R.*, A.area_name";

    // The disabled column didn't always exist and it's possible that this
    // method is being called during an upgrade before the column exists
    $area_columns = Columns::getInstance(_tbl(Area::TABLE_NAME));
    if (null !== $area_columns->getColumnByName('disabled'))
    {
      $sql .= ", A.disabled as area_disabled";
    }

    $sql .= " FROM " . _tbl(static::TABLE_NAME) . " R
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id
             WHERE R." . db()->quote($column) . "=:value
             LIMIT 1";

    $sql_params = array(':value' => $value);
    $res = db()->query($sql, $sql_params);

    if ($res->count() == 0)
    {
      $result = null;
    }
    else
    {
      $class = get_called_class();
      $result = new $class();
      $result->load($res->next_row_keyed());
    }

    return $result;
  }

}
