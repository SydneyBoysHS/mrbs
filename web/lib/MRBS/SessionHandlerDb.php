<?php

namespace MRBS;

// Use our own PHP session handling by storing sessions in the database.   This has three advantages:
//    (a) it's more secure, especially on shared servers
//    (b) it avoids problems with ordinary sessions not working because the PHP session save
//        directory is not writable
//    (c) it's more resilient in clustered environments

class SessionHandlerDb implements \SessionHandlerInterface
{
  private static $table;

  public function __construct()
  {
    self::$table = _tbl('session');

    if (!db()->table_exists(self::$table))
    {
      // We throw an exception if the table doesn't exist rather than returning FALSE, because in some
      // versions of PHP, eg 7.0.25, session_start() will throw a fatal error if it can't open
      // a session, rather than just returning FALSE as the documentation seems to suggest.   So
      // when a new SessionHandlerDb object is created we do it in a try/catch block.  [Note that
      // the exception can't be thrown on open() because a try/catch round session_start() won't
      // catch the exception - maybe because open() is a callback function??]
      //
      // This exception will also be thrown on the upgrade to database schema version 76, when the
      // table was renamed.
      throw new \Exception("MRBS: session table does not exist");
    }
  }

  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function open($path, $name)
  {
    return true;
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function close()
  {
    return true;
  }


  // Returns an encoded string of the read data. If nothing was read, it must
  // return an empty string. Note this value is returned internally to PHP for
  // processing.  Note that the data is base64_encoded in the database (otherwise
  // there were problems with PostgreSQL in storing some objects - needs further
  // investigation).
  public function read($id)
  {
    try
    {
      $sql = "SELECT data
                FROM " . self::$table . "
               WHERE id=:id
               LIMIT 1";

      $result = db()->query1($sql, array(':id' => $id));
    }
    catch (DBException $e)
    {
      // If the exception is because the sessions table doesn't exist, then that's
      // probably because we're in the middle of the upgrade that creates the
      // sessions table, so just ignore it and return ''.   Otherwise re-throw
      // the exception.
      if (!db()->table_exists(self::$table))
      {
        return '';
      }
      throw $e;
    }

    return ($result === -1) ? '' : base64_decode($result);
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.  Note that the data is base64_encoded
  // in the database (see read() above).
  public function write($id, $data)
  {
    $sql = "SELECT COUNT(*) FROM " . self::$table . " WHERE id=:id LIMIT 1";
    $rows = db()->query1($sql, array(':id' => $id));

    if ($rows > 0)
    {
      $sql = "UPDATE " . self::$table . "
                 SET data=:data, access=:access
               WHERE id=:id";
    }
    else
    {
      // The id didn't exist so we have to INSERT it (we couldn't use
      // REPLACE INTO because we have to cater for both MySQL and PostgreSQL)
      $sql = "INSERT INTO " . self::$table . "
                          (id, data, access)
                   VALUES (:id, :data, :access)";
    }

    $sql_params = array(':id' => $id,
                        ':data' => base64_encode($data),
                        ':access' => time());

    db()->command($sql, $sql_params);

    return true;
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function destroy($id)
  {
    $sql = "DELETE FROM " . self::$table . " WHERE id=:id";
    $rows = db()->command($sql, array(':id' => $id));
    return ($rows === 1);
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function gc($max_lifetime)
  {
    $sql = "DELETE FROM " . self::$table . " WHERE access<:old";
    db()->command($sql, array(':old' => time() - $max_lifetime));
    return true;  // An exception will be thrown on error
  }
}
