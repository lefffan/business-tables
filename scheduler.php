<?php

/******************************************************************************************************
field         allowed values
-----         --------------
minute        0-59
hour          0-23
day of month  1-31
month         1-12
day of week   0-7 (0 or 7 is Sunday)

A field may be an asterisk (*), which always stands for 'first-last'. Ranges of numbers are allowed.
Ranges are two numbers separated with a hyphen. The specified range is inclusive. For example, 8-11
for an 'hours' entry specifies execution at hours 8, 9, 10 and 11.
Lists are allowed.  A list is a set of numbers (or ranges) separated by commas.
Examples: '1,2,5,9', '0-4,8-12'.
Step values can be used in conjunction with ranges.  Following a range with '/<number>' specifies
skips of the number's value through the range.
For example, '0-3/2' can be used in the hours field to specify 0,2 hours execution.
******************************************************************************************************/

require_once 'core.php';

function CompareCronField($cronfield, $nowvalue)
{
 if ($cronfield === '*') return true;

 foreach (explode(',', $cronfield) as $value)		// Split cron field on ','
      if (($pos = strpos($value, '/')) !== false)	// Step value?
	 {
	  if (strlen($value) > $pos + 1)		// Some chars after slash?
	  if (CompareCronField(substr($value, 0, $pos), $nowvalue)) // Compare string before the slash
	  if ($nowvalue % intval(substr($value, $pos + 1)) === 0) return true; // and in case of match - test on remainder of division
	 }
       else
	 {
	  if (($pos = strpos($value, '-')) === false && $value === strval($nowvalue)) return true; // No range and exact match?
	  if ($pos !== false && $nowvalue >= intval(substr($value, 0, $pos)) && $nowvalue <= intval(substr($value, $pos + 1))) return true; // Range and match in the range?
	 }

 return false; // No any match? Return false
}

function SplitCronLine($cronline)
{
 if (($cronline = trim($cronline)) === '') return false; // Cron line is empty? Return empty array
 $cronline = explode(' ', $cronline); // Split cron line fields
 $cron = [];

 foreach ($cronline as $key => $value)
      if (count($cron) < count(CRONLINEFIELDS))
         {
	  if (trim($value)) $cron[] = trim($value);	// Datetime and vid fields
	 }
       else
         {
	  isset($cron[count(CRONLINEFIELDS) - 1]) ? $cron[count(CRONLINEFIELDS) - 1] = $value : $cron[count(CRONLINEFIELDS) - 1] .= ' '.$value; // Command line field
	 }

 if (!isset($cron[count(CRONLINEFIELDS) - 1]) || !$cron[count(CRONLINEFIELDS) - 1]) return false;
 return $cron;
}

while (true)
{
 $now = getdate(); // [ 'seconds' => 16, 'minutes' => 30, 'hours' => 2, 'mday' => 11, 'wday' => 0, 'mon' => 4, 'year' => 2021, 'yday' => 100, 'weekday' => 'Sunday', 'month' => 'April', 0 => 1618108216 ]
 $query = $db->prepare("SELECT id,JSON_EXTRACT(odprops, '$.dialog.View') as views,odname,JSON_EXTRACT(odprops, '$.dialog.Element') as elements FROM $");
 $query->execute();

 foreach ($query->fetchAll(PDO::FETCH_NUM) as $od) // Go through all OD structures
	 {
	  $elements = json_decode($od['elements'], true); // Decode current OD structure Element section
	  if (gettype($elements) !== 'array') continue; // Continue if error
	  foreach ($elements as $key => $element) if ($key != 'New element') // Go trough all element profiles except 'New element'
	  foreach ($element as $interface) if (isset($interface['event']) && $interface['event'] === 'SCHEDULE') // Go through all dialog interface elements for the SCHEDULE event
	  foreach (preg_split("/\n/", $interface['data']) as $line => $cronline) // Go through all text lines of SCHEDULE event text area data
		  {
		   // Incorrect cron line? Continue, otherwise exec 'schedulerwrapper code ODid OVid eid': <uniq code> <$od['id']> <$cron[count(CRONLINEFIELDS) - 2]> <$element['element1']['id'] = ''>;
		   if (!($cron = SplitCronLine($cronline))) continue;
		   // Check datetime parameters match
		   for ($i = 0; i < count(CRONLINEFIELDS) - 3; $i++) if (!CompareCronField($cron[$i], $now[CRONLINEFIELDS[$i]])) break 2;
		   // Check correctness of queue and view id cron fields
		   if (!ctype_digit($cron[count(CRONLINEFIELDS) - 2]) || !ctype_digit($cron[count(CRONLINEFIELDS) - 3])) continue;
		   // Current scheduler id loader already does already exist? Continue
		   $output = [];
		   $schedulerwrapperargs = SCHEDULERID.' '.$od['id'].' '.$cron[count(CRONLINEFIELDS) - 2].' '.$element['element1']['id'];
		   exex(SEARCHPROCESSCMD.$schedulerwrapperargs, $output);
		   if (count($output)) continue;
		   // Execute current scheduler id loader
		   exec(SCHEDULERWRAPPERCMD.' '.$schedulerwrapperargs.' >/dev/null &');
		  }
	 }

 $finish = getdate();
 if ($finish['minutes'] === $now['minutes'] && $finish['hours'] === $now['hours']) sleep(60 - $finish['seconds']);
}
