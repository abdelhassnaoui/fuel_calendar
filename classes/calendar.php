<?php

namespace Calendar;

class Calendar {
	
	// instance var for multiple instances
	protected static $_instance = array();

	// config settings
	protected $_view = 'month';
	protected $_navigation = false;
	protected $_navigation_url = null;
	protected $_dates_as_links = true;
	protected $_viewpath = null;
	
	//date settings
	protected $_year;
	protected $_month;
	protected $_day_or_week;
	protected $_first_day;
	protected $_days_in_month;
	protected $_weeks_in_month;
	protected $_data;
	
	/**
	 * Return a new calendar object
	 *
	 * $data array key is the day of month
	 * $data array value is used to display data in the day cell;
	 *
	 * @param array
	 */
	public static function forge($instance = 'default', $config = array())
	{
		return new static($instance, $config);
	}
	
	/**
	 * Return calendar instance
	 */
	public static function instance($instance)
	{
		return static::$_instance[$instance];
	}

	/**
	 * Sets the initial calendar configuration settings
	 *
	 * @param array
	 */
	public function __construct($instance, $config)
	{
		static::$_instance[$instance] = $this;

		foreach($config as $property => $value)
		{
			$property = '_'.$property;
			if(property_exists('Calendar', $property))
			{
				if($property == '_view' and $value != 'week' and $value != 'day')
				{
					$value = 'month';
				}
				$this->$property = $value;
			}
		}
	}
	
	/**
	 * Builds the data array to display in view
	 *
	 * @param int
	 * @param int
	 * @param int
	 * @param array
	 */
	public function build($year = null, $month = null, $day = null, $data = null)
	{
		// convert year and month to ints
		!is_int($year) and $year = (int)$year;
		!is_int($month) and $month = (int)$month;
		!is_int($day) and $day = (int)$day;
		
		// set main vars
		$year == null and $year = date('Y');
		$month == null and $month = date('n');
		$day == null and $day = date('j');
		
		// set values to closest date if date does not exist
		strlen($year) < 4 and $year = (int)str_pad($year, 4, 0, STR_PAD_RIGHT);
		strlen($year) > 4 and $year = (int)substr($year, 0, 4);
		$month > 12 and $month = 12;
		$month < 1 and $month = 1;
		$day > $this->find_days_in_month($month, $year) and $day = $this->find_days_in_month($month, $year);
		$day < 1 and $day = 1;
		
		// check date values
		if ($data and !is_array($data))
		{
			throw new \InvalidArgumentException('The data param only accepts arrays or null');
		}
		
		// set properties
		$this->_month = $month;
		$this->_year = $year;
		$this->_day_or_week = $day;
		$this->_data = $data;
		$this->_days_in_month = $this->find_days_in_month($month, $year);
		$this->_first_day = $this->find_first_weekday_of_month($month, $year);
		$this->_weeks_in_month = $this->find_weeks_in_month($this->_days_in_month, $this->_first_day);
		
		$build = 'build_'.$this->_view;

		// set data
		$data = array(
			//dates
			'days' => $this->get_days(),
			'month' => $this->get_month(),
			'year' => $this->_year,
			'calendar' => $this->$build(),
			//navigation
			'navigation' => $this->_navigation,
			'nav_next' => ($this->_navigation) ? $this->get_navigation('next') : null,
			'nav_prev' => ($this->_navigation) ? $this->get_navigation('previous') : null,
			'nav_month' => ($this->_navigation) ? $this->_navigation_url.'month/'.$year.'/'.$month : null,
			'nav_week' => ($this->_navigation) ? $this->_navigation_url.'week/'.$year.'/'.$month.'/'.$this->get_week() : null,
			'nav_day' => ($this->_navigation) ? $this->_navigation_url.'day/'.$year.'/'.$month.'/'.$this->get_first_day() : null,
		);

		if(!empty($this->_viewpath) and \Fuel::find_file('views/'.$this->_viewpath, 'calendar_'.$this->_view))
		{
			return \View::factory($this->_viewpath.'calendar_'.$this->_view, $data, false);
		}
		return \View::factory('calendar_'.$this->_view, $data, false);
	}
	
	/**
	 * Builds a single month view
	 */
	protected function build_month()
	{
		$data = array();	
		
		//set vars for loop
		$day_of_month = 0;
		$week = 1;

		// start data loop
		while ($day_of_month <= $this->_days_in_month)
		{
			// loop through days in week - Sun = 1
			for( $day_of_week = 1; $day_of_week < 8; $day_of_week++)
			{
				// if add 1 to start month counter when week day = first day
				if ($day_of_week == $this->_first_day and $day_of_month == 0)
				{
					$day_of_month++;
				}
				
				// month cells
				if ($day_of_month > 0 and $day_of_month <= $this->_days_in_month)
				{
					if ($this->_dates_as_links and !isset($this->_data[$day_of_month]['link']))
					{
						$this->_data[$day_of_month]['link'] = $this->_navigation_url.'day/'.$this->_year.'/'.$this->_month.'/'.$day_of_month;
					}
					$data[$week][$day_of_week] = array(
						'date' => ($day_of_month == 0) ? null : $day_of_month,
						'attributes' => isset($this->_data[$day_of_month]['attributes']) ?  $this->_data[$day_of_month]['attributes'] : null,
						'link' => isset($this->_data[$day_of_month]['link']) ?  $this->_data[$day_of_month]['link'] : null,
						'text' => isset($this->_data[$day_of_month]['text']) ? $this->_data[$day_of_month]['text'] : null
					);
					$day_of_month++;
				}
				else // blank cells
				{
					$data[$week][$day_of_week] = array(
						'date' => null,
						'attributes' => null,
						'link' => null,
						'text' => null,
					);
				}
			}
			$week++;
		}
		
		return $data;
	}
	
	/**
	 * Builds a single week view
	 */
	protected function build_week()
	{	
		$month = $this->build_month();

		return $month[$this->_day_or_week];
	}
	
	protected function build_day()
	{
		$data = array(
			'date' => $this->_day_or_week,
			'attributes' => isset($this->_data[$this->_day_or_week]['attributes']) ?  $this->_data[$this->_day_or_week]['attributes'] : null,
			'link' => isset($this->_data[$this->_day_or_week]['link']) ?  $this->_data[$this->_day_or_week]['link'] : null,
			'text' => isset($this->_data[$this->_day_or_week]['text']) ? $this->_data[$this->_day_or_week]['text'] : null
		);
		
		return $data;
	}
	
	/**
	 * Calculates how many days are in a given month
	 *
	 * @param int
	 * @param int
	 */
	public static function find_days_in_month($month, $year)
	{
		return cal_days_in_month(CAL_GREGORIAN, $month, $year); //date('t', mktime(0,0,0,$month,1,$year));
	}
	
	/**
	 * Finds the first weekday of the month
	 *
	 * @param int
	 * @param int
	 */
	public static function find_first_weekday_of_month($month, $year)
	{
		return date('w', mktime(0,0,0,$month,1,$year)) + 1;
	}
	
	/**
	 * Find # of weeks in a month
	 *
	 */
	protected function find_weeks_in_month($days_in_month, $first_day)
	{
		$number_of_days = $days_in_month - ( 7 - ($first_day-1) );
		$number_of_weeks = (1 + (int)( $number_of_days / 7));
		if ($number_of_days % 7 > 0) $number_of_weeks++;
		
		return $number_of_weeks;
	}
	
	/**
	 * Builds navigation and checks dates
	 *
	 * @param string
	 */
	protected function get_navigation($direction)
	{
		if ($direction == 'next') //next week or month
		{
			if ($this->_view == 'week')
			{
				$week = $this->_day_or_week + 1;
				$month = $this->_month;
				$year = $this->_year;
				if ($week > $this->_weeks_in_month)
				{
					$week = 1;
					$month++;
					if($month > 12)
					{
						$month = 1;
						$year++;
					}
				}
				return $this->_navigation_url.$this->_view.'/'.$year.'/'.$month.'/'.$week;
			}
			else if ($this->_view == 'day')
			{
				$day = $this->_day_or_week + 1;
				$month = $this->_month;
				$year = $this->_year;
				if ($day > $this->_days_in_month)
				{
					$day = 1;
					$month++;
					if ($month > 12)
					{
						$month = 1;
						$year++;
					}
				}
				return $this->_navigation_url.$this->_view.'/'.$year.'/'.$month.'/'.$day;
			}
			else
			{
				// get next month
				$month = $this->_month + 1;
				$year = $this->_year;
				if ($month > 12)
				{
					$month = 1;
					$year++;
				}	
			}
			return $this->_navigation_url.$this->_view.'/'.$year.'/'.$month;
		}
		else //previous week or month
		{
			if ($this->_view == 'week')
			{
				$week = $this->_day_or_week - 1;
				$month = $this->_month;
				$year = $this->_year;
				if ($week <= 0)
				{ 
					$month--;
					if ($month <= 0)
					{
						$month = 12;
						$year--;
					}
					$week = $this->find_weeks_in_month($this->find_days_in_month($month, $year), $this->find_first_weekday_of_month($month, $year));
				}
				return $this->_navigation_url.$this->_view.'/'.$year.'/'.$month.'/'.$week;
			}
			else if ($this->_view == 'day')
			{
				$day = $this->_day_or_week - 1;
				$month = $this->_month;
				$year = $this->_year;
				if ($day <= 0)
				{
					$month--;
					if ($month <= 0)
					{
						$month = 12;
						$year--;
					}
					$day = $this->find_days_in_month($month, $year);
				}
				return $this->_navigation_url.$this->_view.'/'.$year.'/'.$month.'/'.$day;
			}
			else
			{
				$month = $this->_month - 1;
				$year = $this->_year;
				if($month <= 0)
				{
					$month = 12;
					$year--;
				}
				return $this->_navigation_url.$this->_view.'/'.$year.'/'.$month;
			}
		}
	}
	
	/**
	 * Gets days of week
	 */
	protected function get_days()
	{
		$days = array(
			'1' => 'Sunday',
			'2' => 'Monday',
			'3' => 'Tuesday',
			'4' => 'Wednesday',
			'5' => 'Thursday',
			'6' => 'Friday',
			'7' => 'Saturday'
		);
		
		return $days;
	}
	
	/**
	 * Gets current month
	 */
	protected function get_month()
	{
		$months = array(
			'1' => 'January',
			'2' => 'February',
			'3' => 'March',
			'4' => 'April',
			'5' => 'May',
			'6' => 'June',
			'7' => 'July',
			'8' => 'August',
			'9' => 'September',
			'10' => 'October',
			'11' => 'November',
			'12' => 'December'
		);
		
		return $months[$this->_month];
	}
	
	/**
	 * Get first day of week or month
	 */
	protected function get_first_day()
	{
		if ($this->_view == 'month')
		{
			return 1;
		}
		else if ($this->_view == 'week')
		{
			$month = $this->build_month();
			return $month[$this->_day_or_week][1]['date'];
		}
		return $this->_day_or_week;
	}
	
	/**
	 * Get first week of month or current week of the selected day
	 */
	protected function get_week()
	{
		if ($this->_view == 'month')
		{
			return 1;
		}
		else if ($this->_view == 'day')
		{
			$month = $this->build_month();
			foreach ($month as $week_num => $week)
			{
				foreach ($week as $day)
				{
					if ($day['date'] == $this->_day_or_week)
					{
						return $week_num;
					}
				}
			}
			return 1;
		}
		return $this->_day_or_week;
	}
}