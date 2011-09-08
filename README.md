# Fuel Calendar Package

A calendar package for fuel. Displays month, week or day views with data.

## About

* Version : 1.0
* Author : Daniel Petrie

## Installation

Download the package and move it into your fuel/packages/ directory

## Usage

```php
// some controller method

public function action_calendar($view = null, $year = null, $month = null, $day = null)
{
	$config = array(
		'dates_as_links' => true,
		'navigation' => true,
		'navigation_url' => \Config::get('base_url').'events/view/calendar/',
		'viewpath' => 'events/content/',
	);

	$this->response->body = \Calendar::forget('calendar', $config)->build($view, $year, $month, $day);
}
```

Here is some very basic styling if you wish to use it

```php
.fuel_calendar header {
	margin: 0 0 10px;
	padding: 8px;
	background: #ececec;
	border: 1px solid #a3a3a3;
	border-radius: 6px;
	overflow: hidden;
	zoom: 1;
}

.fuel_calendar header h3 {
	float: left;
}

.fuel_calendar header nav {
	float: right;
	border: 1px solid #a3a3a3;
	background: #f6f6f6;
	border-radius: 6px;
}

.fuel_calendar header nav li {
	display: inline;
}

.fuel_calendar header nav a {
	display: block;
	float: left;
	padding: 3px 5px;
	color: #000;
	text-decoration: none;
	border-right: 1px solid #a3a3a3;
}

.fuel_calendar header nav li a.selected {
	background: #A3A3A3;
	color: white;
	box-shadow: 1px 1px 1px #333 inset;
	text-shadow: 1px 1px 0 #000;
}

.fuel_calendar header nav li:last-child a {
	border-right: 0;
}

.fuel_calendar table {
	width: 90%;
	margin: 0px auto;
}
.fuel_calendar td {
	border: 1px solid #000;
	padding: 10px;
	height: 100px;
	width: 100px;
}
```

## Config Options

'dates_as_links' => (true or false) // dates that appear in the calendar will link to the day

'navigation' => (true or false) // builds a navigation for easy date manipulation

'navigation_url' => 'url' // the url to the current controller/method - must end wiht a '/',

'viewpath' => 'path' // use this if you wish to overwrite the template that comes with the package and the file location is not located in the base view folder. If the view file is located in 'view/calendar/calendar_week.php' the path should be 'calendar/'.


## Templating

Look at the view files in the package. To modify create the same exact file name in your base 'app/view' folder or use the 'viewpath' config option if you want to have them in a subdirectory of 'app/view'.