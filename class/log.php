<?php
  class Log
    {
      private static function addEntry($str)
      {
        $handle = fopen('log/log.log', 'a');
        fwrite($handle, sprintf("%s %s\n", date('c'), $str));
        fclose($handle);
      }

      public static function warn($str)
      {
        self::addEntry("WARNING $str");
      }

      public static function info($str)
      {
        self::addEntry("INFO $str");
      }

      public static function debug($str)
      {
        self::addEntry("DEBUG $str");
      }
    }