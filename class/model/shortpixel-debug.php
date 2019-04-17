<?php
// The data models.
namespace ShortPixel;


class DebugItem // extends ShortPixelModel Too early init for this.
{
    protected $time;
    protected $level;
    protected $message;
    protected $data = array();

    protected $model;

    const LEVEL_ERROR = 1;
    const LEVEL_WARN = 2;
    const LEVEL_INFO = 3;
    const LEVEL_DEBUG = 4;

    public function __construct($message, $args)
    {
        $this->level = $args['level'];
        $data = $args['data'];

        $this->message = $message;
        $this->time = microtime(true);

        // Add message to data if it seems to be some debug variable.
        if (is_object($this->message) || is_array($this->message))
        {
          $data[] = $this->message;
          $this->message = __('[Data]');
        }
        if (is_array($data) && count($data) > 0)
        {
          $dataType = $this->getDataType($data);
          if ($dataType == 1)  // singular
          {
              $this->data[] = print_r($data, true);
          }
          if ($dataType == 2)
          {
            foreach($data as $index => $item)
            {
              if (is_object($item) || is_array($item))
              {
                $this->data[$index] = print_r($item, true);
              }
            }
          }
        } // if
        elseif (! is_array($data)) // this leaves out empty default arrays
        {
           $this->data[] = print_r($data, true);
        }
    }

    public function getData()
    {
      return array('time' => $this->time, 'level' => $this->level, 'message' => $this->message, 'data' => $this->data);
    }

    /** Test Data Array for possible values
    *
    * Data can be a collection of several debug vars, a single var, or just an normal array. Test if array has single types,
    * which is a sign the array is not a collection.
    */
    protected function getDataType($data)
    {
        $single_type = array('integer', 'boolean', 'string');
        if (in_array(gettype(reset($data)), $single_type))
        {
          return 1;
        }
        else
        {
          return 2;
        }
    }

    public function getForFormat()
    {
      switch($this->level)
      {
          case self::LEVEL_ERROR:
            $level = 'ERR';
            $color = "\033[31m";
          break;
          case self::LEVEL_WARN:
            $level = 'WRN';
            $color = "\033[33m";
          break;
          case self::LEVEL_INFO:
            $level = 'INF';
            $color = "\033[37m";
          break;
          case self::LEVEL_DEBUG:
            $level = 'DBG';
            $color = "\033[37m";
          break;

      }
      $color_end = "\033[0m";

      return array('time' => $this->time, 'level' => $level, 'message' => $this->message, 'data' => $this->data, 'color' => $color, 'color_end' => $color_end);

    }


}