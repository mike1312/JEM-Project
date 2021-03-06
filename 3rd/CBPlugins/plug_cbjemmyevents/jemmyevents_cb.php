<?php
/**
 * @package My Events
 * @version JEM v2.0 / v2.1 & CB v1.9 / v2.0
 * @author JEM Community
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2
 *
 * Just a note:
 * Keep the query code inline with my-events view
 *
 */

defined('_JEXEC') or die;


@include_once (JPATH_SITE.'/components/com_jem/classes/image.class.php');
@include_once (JPATH_SITE.'/components/com_jem/classes/Zebra_Image.php');
@include_once (JPATH_SITE.'/components/com_jem/classes/output.class.php');
@include_once (JPATH_SITE.'/components/com_jem/helpers/helper.php');
@include_once (JPATH_SITE.'/components/com_jem/helpers/route.php');


class jemmyeventsTab extends cbTabHandler {

	protected $jemFound = false;

	/**
	 * Show My Events
	 */
	function __construct()
	{
		// Check if JEM is installed.
		$this->jemFound = class_exists('JemImage') && class_exists('JemOutput') && class_exists('JemHelperRoute');

		$this->cbTabHandler();
	}


	/**
	 * Retrieve the languagefile
	 * The file is located in the folder language
	 */
	function _getLanguageFile()
	{
		global $_CB_framework;
		$UElanguagePath=$_CB_framework->getCfg('absolute_path').'/components/com_comprofiler/plugin/user/plug_cbjemmyevents';
		if (file_exists($UElanguagePath.'/language/'.$_CB_framework->getCfg('lang').'.php')) {
			include_once($UElanguagePath.'/language/'.$_CB_framework->getCfg('lang').'.php');
		} else {
			include_once($UElanguagePath.'/language/english.php');
		}
	}


	/**
	 * Returns a list of categories corresponding to given event id.
	 * @param  int   $eventId ID of the event
	 * @param  array $levels  List of view access levels
	 * @return array List of categories
	 */
	protected function getCategories($eventId, $levels)
	{
		/* loading global variables */
		global $_CB_database;

		if (!is_array($levels) || empty($levels)) {
			$levels = array(1);
		}

		$query = 'SELECT DISTINCT c.id AS catid, c.catname,'
				. ' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as catslug'
				. ' FROM #__jem_categories AS c'
				. ' LEFT JOIN #__jem_cats_event_relations AS rel ON rel.catid = c.id'
				. ' WHERE rel.itemid = '.(int)$eventId
				. ' AND c.published = 1'
				. ' AND c.access IN (' . implode(',', $levels) . ')'
		;
		$_CB_database->setQuery($query);
		$results = $_CB_database->loadObjectList();

		return $results;
	}


	/**
	 * Display Tab
	 */
	function getDisplayTab($tab, $user, $ui)
	{
		/* loading global variables */
		global $_CB_database,$_CB_framework;

		if (!$this->jemFound) {
			return '';
		}

		/* loading the language function */
		self::_getLanguageFile();

		/*loading params set by the backend*/
		$params = $this->params;

		/* message at the bottom of the table */
		$event_tab_message = $params->get('hwTabMessage', "");

		/* variables */
		$return = null;

		$event_image = $params->get('event_image');
		$end_date = $params->get('end_date');
		$start_date = $params->get('start_date');
		$event_categories = $params->get('event_categories');
		$event_attending = $params->get('event_attending');

		/* load css */
		//$_CB_framework->addCustomHeadTag("<link href=\"".$_CB_framework->getCfg('live_site')."/components/com_comprofiler/plugin/user/plug_cbjemmyevents/jemmyevents_cb.css\" rel=\"stylesheet\" type=\"text/css\" />");
		$_CB_framework->document->addHeadStyleSheet($_CB_framework->getCfg('live_site').'/components/com_comprofiler/plugin/user/plug_cbjemmyevents/jemmyevents_cb.css');

		/*
		 * Tab description
		 *
		 * the text will be on top of the table
		 * can be filled in the backend, section: Tab management
		 */

		if (!empty($tab->description)) {
			// html content is allowed in descriptions
			$return .= "\t\t<div class=\"tab_Description\">". $tab->description . "</div>\n";
		}

		// retrieval user parameters
		$userid = $user->id;

		// Support Joomla access levels instead of single group id
		// Note: $user is one which profile is requested, not the asking user!
		//       $juser is the asking user which view access levels must be used.
		$juser = JFactory::getUser();
		$levels = $juser->getAuthorisedViewLevels();

		/*
		 * Query
		 *
		 * Retrieval of the data
		 * Keep it inline with the my-events view
		 */
		$query = 'SELECT DISTINCT a.id AS eventid, a.id, a.dates, a.datimage, a.enddates, a.times, a.endtimes, a.title, a.created, a.locid, CONCAT(a.introtext,a.fulltext) AS text, a.published, a.registra, a.maxplaces, a.waitinglist,'
		//	. ' l.venue, l.city, l.state, l.url,'
			. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug'
		//	. ',CASE WHEN CHAR_LENGTH(l.alias) THEN CONCAT_WS(\':\', a.locid, l.alias) ELSE a.locid END as venueslug'
			. ' FROM `#__jem_events` AS a '
		//	. ' LEFT JOIN `#__jem_venues` AS l ON l.id = a.locid '
			. ' LEFT JOIN #__jem_cats_event_relations AS rel ON rel.itemid = a.id '
			. ' LEFT JOIN #__jem_categories AS c ON c.id = rel.catid '
			. ' WHERE a.published = 1 AND c.published = 1 AND a.created_by = '.$userid
			. '  AND a.access IN (' . implode(',', $levels) . ') AND c.access IN (' . implode(',', $levels) . ')'
			. ' GROUP BY a.id'
			. ' ORDER BY a.dates, a.times'
			;
		$_CB_database->setQuery($query);
		$results = $_CB_database->loadObjectList();

		/* Headers
		 *
		 * The classes are retrieved from:
		 * components/com_comprofiler/plugin/user/plug_cbjemmyevents/jemmyevents_cb.css
		 *
		 * The language strings are retrieved from:
		 * components/com_comprofiler/plugin/user/plug_cbjemmyevents/language/*languagecode*
		 *
		 * defining a new language can be done like:
		 * - add a new string, like: _JEMMYEVENTS_NEWNAME
		 * - add the translation to the language file
		 */

		/* start of form */
		$return .= "\n\t<form method=\"post\" name=\"jemmyeventsForm\">";

		/* Start of Table */
		$return .= "\n\t<table  class='jemmyeventsCBTabTable'>";

		/* start of headerline */
		$return .= "\n\t\t<tr class='jemmyeventstableheader'>";

		/* start of imagefield */
		if ($event_image) {
			$return .= "\n\t\t\t<th class='jemmyeventsCBTabTableTitle'>";
			$return .= "\n\t\t\t\t" . _JEMMYEVENTS_IMAGE;
			$return .= "\n\t\t\t</th>";
		}

		/* Title header */
		$return .= "\n\t\t\t<th class='jemmyeventsCBTabTableTitle'>";
		$return .= "\n\t\t\t\t" . _JEMMYEVENTS_TITLE;
		$return .= "\n\t\t\t</th>";

		/* Category header */
		if ($event_categories) {
			$return .= "\n\t\t\t<th class='jemmyeventsCBTabTableCat'>";
			$return .= "\n\t\t\t\t" . _JEMMYEVENTS_CATEGORY;
			$return .= "\n\t\t\t</th>";
		}

		/* Startdate header */
		if ($start_date) {
			$return .= "\n\t\t\t<th class='jemmyeventsCBTabTableStart'>";
			$return .= "\n\t\t\t\t" . _JEMMYEVENTS_START;
			$return .= "\n\t\t\t</th>";
		}

		/* Enddate header */
		if ($end_date) {
			$return .= "\n\t\t\t<th class='jemmyeventsCBTabTableExp'>";
			$return .= "\n\t\t\t\t" . _JEMMYEVENTS_EXPIRE;
			$return .= "\n\t\t\t</th>";
		}

		/* Attendees */
		if ($event_attending) {
			$return .= "\n\t\t\t<th class='jemmyeventsCBTabTableReg'>";
			$return .= "\n\t\t\t\t" . _JEMMYEVENTS_REGISTER;
			$return .= "\n\t\t\t</th>";
		}

		/* End of headerline */
		$return .= "\n\t\t</tr>";

		/*
		 * Counting data
		 * If data is available start with the rows
		 */
		if (count($results)) {
			$config = JemHelper::config();
			$formatShortDate = $config->formatShortDate;

			$odd = 1;
			foreach ($results as $result) {
				$odd = ($odd+1)%2; // toggle {0, 1} for alternating row css classes

				/*
				 * Start of rowline
				 *
				 * The variable for the tr class has been defined above
				 * result stands for the variables of the query
				 */
				$return .= "\n\t\t<tr class='row{$odd}'>";

				/* Image field */
				if ($event_image) {
					$dimage =	JEMImage::flyercreator($result->datimage, 'event');
					$pimage =	JEMOutput::flyer($result, $dimage, 'event');
					$return .= "\n\t\t\t<td class='jemmyeventsCBTabTableImage'>";
					$return .= "\n\t\t\t\t{$pimage}";
					$return .= "\n\t\t\t</td>";
				}

				/* Title field */
				$return .= "\n\t\t\t<td class='jemmyeventsCBTabTableTitle'>";
				$return .= "\n\t\t\t\t<a href=\"". JRoute::_(JEMHelperRoute::getEventRoute($result->slug)) ."\">{$result->title}</a>";
				$return .= "\n\t\t\t</td>";

				/* Category field */
				if ($event_categories) {
					$categories = $this->getCategories($result->eventid, $levels);
					$cats = array();
					if (is_array($cats)) {
						foreach ($categories as $cat) {
							$cats[] = "<a href='".JRoute::_(JEMHelperRoute::getCategoryRoute($cat->catslug))."'>{$cat->catname}</a>";
						}
					}
					if (empty($cats)) {
						$cats[] = '-';
					}
					$return .= "\n\t\t\t<td class='jemmyeventsCBTabTableCat'>";
					$return .= "\n\t\t\t\t".implode(', ', $cats);
					$return .= "\n\t\t\t</td>";
				}

				/* Startdate field */
				if ($start_date) {
					$startdate2 =	JEMOutput::formatdate($result->dates, $formatShortDate);
					$return .= "\n\t\t\t<td class='jemmyeventsCBTabTableStart'>";
					$return .= "\n\t\t\t\t{$startdate2}";
					$return .= "\n\t\t\t</td>";
				}

				/*
				 * Enddate
				 * if no enddate is given nothing will show up
				 */
				if ($end_date) {
					$enddate2 =	JEMOutput::formatdate($result->enddates, $formatShortDate);
					$return .= "\n\t\t\t<td class='jemmyeventsCBTabTableExp'>";
					$return .= "\n\t\t\t\t{$enddate2}";
					$return .= "\n\t\t\t</td>";
				}

				/* Attendees field */
				if ($event_attending) {
					$regs = '-';
					if ($result->registra) {
						$qry = "SELECT count(uid) AS regs FROM #__jem_register where `event`=$result->eventid AND waiting=0";
					$_CB_database->setQuery($qry);
						$regs = (int)$_CB_database->loadObjectList()[0]->regs;
						$qry = "SELECT count(uid) AS regs FROM #__jem_register where `event`=$result->eventid AND waiting>0";
						$_CB_database->setQuery($qry);
						$waits = (int)$_CB_database->loadObjectList()[0]->regs;
						if ($result->maxplaces) {
							$regs .= ' / '.(int)$result->maxplaces;
							if ($result->waitinglist && $waits) {
								$regs .= ' + '.$waits;
							}
						}
					}

					$return .= "\n\t\t\t<td class='jemmyeventsCBTabTableReg'>";
					$return .= "\n\t\t\t\t{$regs}";
					$return .= "\n\t\t\t</td>";
				}

				/* Closing the rowline */
				$return .= "\n\t\t</tr>";
			} // end of displaying rows
		} else {
			// When no data has been found the user will see a message

			/* display no listings */
			$return .= '<tr><td class="jemmyattendingCBTabTableTitle" span="9">'._JEMMYEVENTS_NO_LISTING.'</td></tr>';
		}

		/* closing tag of the table */
		$return .="</table>";

		/* closing of the form */
		$return .="</form>";

		/*
		 * Message for at the bottom, below the table
		 *
		 * At the top we did specify the variable
		 * but not sure where we can fill it
		 */
		$return .= "\t\t<div>\n<p>". htmlspecialchars($event_tab_message). "</p></div>\n";

			/*
		 * Showing the code
			 *
			* We did specify the code above, but we do want to display it to the user
			* There were a lot of "$return ." and all of them will be printed.
			*/
			return $return;
	}
}
?>
