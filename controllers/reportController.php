<?php

  /** The php backend for the main editing stuff. Mostly just creates a bunch of json data. All
   the exciting stuff is JavaScript. Which is scary.
   */
class ReportController
extends Controller
{
    function baseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm);
    }

    function nextBaseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm + 7* 3600*24);
    }

    function prevBaseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm - 7* 3600*24);
    }

    function nowBaseDateStr()
    {
        return date('Y-m-d');
    }

    function viewRun()
    {
        $content = "";
        $content .= "<div id='debug' style='position:absolute;right: 100px;'></div>";
        $content .= "<div id='dnd_text' style='position:absolute;left: 0px;top: 0px; display:none;'></div>";

        util::setTitle("Reporting");

        $next = self::nextBaseDateStr();
        $prev = self::prevBaseDateStr();
        $now = self::nowBaseDateStr();
        $prev_link = makeUrl(array('date'=>$prev));
        $next_link = makeUrl(array('date'=>$next));
        $now_link = makeUrl(array('date'=>$now));
        $content .= "<p><a href='$prev_link'>«earlier</a> <a href='$now_link'>today</a>  <a href='$next_link'>later»</a></p>";

        $form = "";
	$hidden = array_merge($_GET, array('controller' => 'report'));
	unset($hidden['reports']);
	$reports = isset($_GET['reports']) ? intval($_GET['reports']) : 1;
	$form .= 'Number of reports: ' . form::makeText('reports', $reports, null, null, array('onchange'=>'submit();'));
	$content .= form::makeForm($form, $hidden, 'get');


        $form = "";
	$hidden = array('controller' => 'report', 'reports' => $reports);
	if (param('date')) $hidden['date']  = param('date');

	$all_users = User::getAllUsers();
	$all_tags = Tag::fetch();
	$all_projects = Project::getProjects();

	for ($report = 0; $report < $reports; $report++) {
            $form .= "<div class='report_form_part'>";
	    $form .= "<table>
		       <tr><th>Users</th><th>Tags</th><th>Projects</th></tr>
		       <tr>";

	    $users = isset($_GET['users_'.$report]) ? $_GET['users_'.$report] : array();
	    $form .= "<td>" . form::makeSelect('users_'.$report, form::makeSelectList($all_users, 'name', 'fullname'), $users, null, array('onchange'=>'submit();')) . "</td>";

	    $tags = isset($_GET['tags_'.$report]) ? $_GET['tags_'.$report] : array();
	    $form .= "<td>" . form::makeSelect('tags_'.$report, form::makeSelectList($all_tags, 'name', 'name'), $tags, null, array('onchange'=>'submit();')) . "</td>";

	    $projects = isset($_GET['projects_'.$report]) ? $_GET['projects_'.$report] : array();
	    $form .= "<td>" . form::makeSelect('projects_'.$report, form::makeSelectList($all_projects, 'name', 'name'), $projects, null, array('onchange'=>'submit();')) . "</td>";

	    $form .= "</tr></table>";

	    $show_graph = isset($_GET['show_graph_'.$report]) ? $_GET['show_graph_'.$report] == 't' : true;
	    $show_hour_list = isset($_GET['show_hour_list_'.$report]) ? $_GET['show_hour_list_'.$report] == 't' : true;
	    $show_hour_summary_per_user = isset($_GET['show_hour_summary_per_user_'.$report]) ? $_GET['show_hour_summary_per_user_'.$report] == 't' : true;
	    $show_hour_summary = isset($_GET['show_hour_summary_'.$report]) ? $_GET['show_hour_summary_'.$report] == 't' : true;

	    $hour_list_order = isset($_GET['hour_list_order_'.$report]) ? explode(',', $_GET['hour_list_order_'.$report]) : array('perform_date','user_fullname','project,tag_names');

	    $form .= form::makeCheckbox('show_graph_'.$report, $show_graph, "Graph", null, null, array('onchange'=>'submit();'));
	    $form .= form::makeCheckbox('show_hour_list_'.$report, $show_hour_list, "Hour list", null, null, array('onchange'=>'submit();'));
	    $form .= form::makeCheckbox('show_hour_summary_per_user_'.$report, $show_hour_summary_per_user, "Hour summary per user", null, null, array('onchange'=>'submit();'));
	    $form .= form::makeCheckbox('show_hour_summary_'.$report, $show_hour_summary, "Total hour summary", null, null, array('onchange'=>'submit();'));
	    $form .= '<div>Sort order for hour list: ' . form::makeText('hour_list_order_'.$report, implode(',', $hour_list_order), null, null, array('onchange'=>'submit();')) . "</div>";

	    $form .= "</div>";
	}
        $content .= form::makeForm($form, $hidden, 'get');
	$content .= "<div class='report_form_end'></div>";

	for ($report = 0; $report < $reports; $report++) {
	    $users = isset($_GET['users_'.$report]) ? $_GET['users_'.$report] : array();
	    $tags = isset($_GET['tags_'.$report]) ? $_GET['tags_'.$report] : array();
	    $projects = isset($_GET['projects_'.$report]) ? $_GET['projects_'.$report] : array();
	    $show_graph = isset($_GET['show_graph_'.$report]) ? $_GET['show_graph_'.$report] == 't' : true;
	    $show_hour_list = isset($_GET['show_hour_list_'.$report]) ? $_GET['show_hour_list_'.$report] == 't' : true;
	    $show_hour_summary_per_user = isset($_GET['show_hour_summary_per_user_'.$report]) ? $_GET['show_hour_summary_per_user_'.$report] == 't' : true;
	    $show_hour_summary = isset($_GET['show_hour_summary_'.$report]) ? $_GET['show_hour_summary_'.$report] == 't' : true;

	    $hour_list_order = isset($_GET['hour_list_order_'.$report]) ? explode(',', $_GET['hour_list_order_'.$report]) : array('perform_date','user_fullname','project','tag_names');

	    if ($show_graph) {
	        $params = array('controller'=>'graph', 'width' => '1024', 'height' => '480', 'date' => param('date'));
		foreach ($_GET as $name => $value) {
		    if (util::ends_with($name, "_{$report}")) {
		        $name = substr($name, 0, strlen($name)-strlen("_{$report}"));
		        $params[$name] = $value;
		    }
		}
		$content .= "<div class='figure'><img src='" . makeUrl($params) . "' /></div>";
	    }

	    $date_end = date('Y-m-d',Entry::getBaseDate());
	    $date_begin = date('Y-m-d',Entry::getBaseDate()-(Entry::getDateCount()-1)*3600*24);

	    $all = User::getAllUsers();
	    $user_ids = array();
	    foreach (param('users',array()) as $usr) {
		$user_ids[] = $all[$usr]->id;
	    }

	    $filter = array(
	     'date_begin' => $date_begin,
	     'date_end' => $date_end,
	     'projects' => isset($_GET['projects_'.$report]) ? $_GET['projects_'.$report] : array(),
	     'tags' => isset($_GET['tags_'.$report]) ? $_GET['tags_'.$report] : array(),
	     'users' => $user_ids
	    );
	    $colors = Entry::colors($filter);
	    $hours_by_date = Entry::coloredEntries($filter, $hour_list_order);

	    $color_to_idx = array();
	    $idx_to_color = array();
	    $idx_to_tag_names = array();
	    $idx = 0;
	    foreach ($colors as $color) {
		$idx_to_color[$idx] = array($color['color_r'], $color['color_g'], $color['color_b']);
		$idx_to_tag_names[$idx] = $color['tag_names'];
		$colorname = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
		$color_to_idx[$colorname] = $idx;
		$idx++;
	    }

	    /* Sum stuff up */
	    $sums = array('total' => array('total' => 0));
	    foreach ($hours_by_date as $date => $hours) {
		foreach ($hours as $hour) {
		    $usr = $hour['user_fullname'];
		    $color = util::colorToHex($hour['color_r'], $hour['color_g'], $hour['color_b']);
		    if (!isset($sums[$usr])) $sums[$usr] = array('total' => 0);
		    if (!isset($sums[$usr][$color])) $sums[$usr][$color] = 0;
		    if (!isset($sums['total'][$color])) $sums['total'][$color] = 0;
		    $sums[$usr][$color] += $hour['minutes'];
		    $sums[$usr]['total'] += $hour['minutes'];
		    $sums['total'][$color] += $hour['minutes'];
		    $sums['total']['total'] += $hour['minutes'];
		}
	    }

	    if ($show_hour_list) {
	        $columns = array('perform_date' => 'Date', 'minutes' => 'Minutes', 'user_fullname' => 'User', 'project' => 'Project', 'tag_names' => 'Tags', 'description' => 'Description');
                $ordered_columns = array();
		foreach ($hour_list_order as $col) {
		    $ordered_columns[$col] = $columns[$col];
		    unset($columns[$col]);
		}
		$columns = array_merge($ordered_columns, $columns);

		$content .= "<table class='report_timetable'>";
		$content .= " <tr>";
		foreach($columns as $col => $col_desc) {
		    if ($col == 'tag_names') {
		        $content .= "<th></th>";
 		    }
		    $content .= "<th>{$col_desc}</th>";
	        }
		$content .= " </tr>";

		foreach ($hours_by_date as $hours) {
		    foreach ($hours as $hour) {
			$content .= " <tr>";
			$first = true;
			foreach($columns as $col => $col_desc) {
			    $tag = $first ? 'th' : 'td';
			    $value = $hour[$col];
			    if ($col == 'perform_date')
			        $value = date('Y-m-d', $value);
			    if ($col == 'tag_names') {
			        $color = util::colorToHex($hour['color_r'], $hour['color_g'], $hour['color_b']);
			        $content .= "<{$tag} style='background: {$color}'>&nbsp;</{$tag}>";
			    }
			    $content .= "<{$tag}>{$value}</{$tag}>";
			    $first = false;
			}
			$content .= " </tr>";
		    }
		}
		$content .= "</table>";
	    }

	    if ($show_hour_summary_per_user) {
		$content .= "<table class='report_timetable'>";
		$content .= " <tr>";
		$content .= "  <th>User</th>";
		foreach ($colors as $color) {
		    $content .= "<th>{$color['tag_names']}</th>";
		}
		$content .= "  <th>Total</th>";
		$content .= " </tr>";
		$content .= " <tr>";
		$content .= "  <th></th>";
		foreach ($colors as $color) {
		    $color = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
		    $content .= "<td style='background: {$color}'>&nbsp;</td>";
		}
		$content .= "  <td></td>";
		$content .= " </tr>";

		foreach ($sums as $usr => $color_sums) {
		    if ($usr != 'total') {
			$content .= "<tr><th>{$usr}</th>";
			foreach ($colors as $color) {
			    if ($color != 'total') {
				$color = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
				if (isset($color_sums[$color])) {
				    $content .= "<td>{$color_sums[$color]}</td>";
				} else {
				    $content .= "<td></td>";
				}
			    }
			}
			$content .= "<td>{$color_sums['total']}</td>";
			$content .= " </tr>";
		    }
		}
	    }

	    if ($show_hour_summary) {
		$content .= "<table class='report_timetable'>";
		$content .= "<tr><th>Tags</th><th></th><th>Minutes</th></tr>";
		foreach ($sums['total'] as $color => $sum) {
		    if ($color != 'total') {
			$tags = $idx_to_tag_names[$color_to_idx[$color]];
			$content .= "<tr><th>{$tags}</th><td style='background: {$color}'>&nbsp;</td><td>{$sum}</td>";
		    }
		}
		$content .= "<tr><th>Total</th><td></td><td>{$sums['total']['total']}</td>";

		$content .= "</table>";
	    }
	}

        $this->show(null, $content);

    }
    
    
      
}


?>