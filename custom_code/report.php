<?php
//PATH TO YOUR HELPSPOT CONFIG FILE
include('../config.php');

error_reporting(E_ERROR | E_PARSE);

//PATH TO HELPSPOTS ADODB FILES
include(cBASEPATH.'/helpspot/adodb/adodb.inc.php');
include(cBASEPATH.'/helpspot/lib/util.lib.php');

//CREATE DB CONNECTION
$db = &ADONewConnection(cDBTYPE);
if(!$db->Connect(cDBHOSTNAME, cDBUSERNAME, cDBPASSWORD, cDBNAME)){
    echo 'Unable to connect to database';
    return false;
}

//ADODB SETTING
$db->SetFetchMode(ADODB_FETCH_ASSOC);

/***************************
 * Convenience Functions
/****************************/
function gmtToTime($timeStamp, $selectedTz)
{
    $selectedTz = new \DateTimeZone($selectedTz);

    $dateTime = \DateTime::createFromFormat('U', $timeStamp);

    $dateTime->setTimezone($selectedTz);
    return $dateTime->format('Y/m/d h:i A');
}

function unserializeIf($string, $format=true)
{
    if( strpos($string, 'ccstaff') !== false )
    {
        $object = unserialize($string);
        return logObjectToString($object, $format);
    }

    if( $format )
    {
        $string = str_replace('Reassigned', '<strong>Reassigned</strong>', $string);
        $string = str_replace('Request changed', '<strong>Request changed</strong>', $string);
        $string = str_replace('Status changed', '<strong>Status changed</strong>', $string);
        $string = str_replace('Category changed', '<strong>Category changed</strong>', $string);
        $string = str_replace('ERROR', '<strong>ERROR</strong>', $string);
        $string = str_replace('Email subject changed', '<strong>Email subject changed</strong>', $string);
        $string = str_replace('Customer email changed', '<strong>Customer email changed</strong>', $string);
        $string = str_replace('Customer ID changed', '<strong>Customer ID changed</strong>', $string);
        $string = str_replace('Custom field', '<strong>Custom field</strong>', $string);
        $string = nl2br($string);
    }
    return $string;
}

function logObjectToString($object, $format=true)
{
    if( $format )
    {
        return '<strong>Customer Emailed:</strong><br /><ul><li><strong>Subject</strong>: '.$object['sTitle'].'</li><li><strong>Customer</strong>: '.$object['customeremail'].'</li></ul>';
    }
    return 'Customer Emailed. Subject: '.$object['sTitle'].' Customer: '.$object['customeremail'];
}

/***************************
 * Status Handling
/****************************/
$statuses = array();

function getLastStatus($xRequest, $default='Active')
{
    global $statuses;

    if( isset($statuses[$xRequest]) )
    {
        $status = end($statuses[$xRequest]);
        reset($statuses[$xRequest]);
        return (is_null($status)) ? $default : $status;
    }

    return $default;
}

function parseStatusChange($tLog)
{
    // If it's a serialized object
    if( strpos($tLog, 'ccstaff') !== false )
    {
        return null;
    }

    $logs = explode("\n", $tLog);

    $parsedStatuses = array();

    foreach( $logs as $entry )
    {
        if( strpos($entry, 'Status changed') !== false )
        {
            preg_match_all('/("[^"]*")|[^"]*/i', $entry, $matches);

            if( isset($matches[1]) )
            {
                foreach($matches[1] as $quotedString)
                {
                    $withoutQuotes = trim(str_replace('"', '', $quotedString));
                    if( strlen($withoutQuotes) > 0 )
                    {
                        $parsedStatuses[] = $withoutQuotes;
                    }
                }
            }
        }
    }

    $lastStatus = end($parsedStatuses);

    return ($lastStatus) ? $lastStatus : null;
}

/***************************
 * Get Users
/****************************/
$users = $db->Execute( 'SELECT xPerson, sFname, sLname, '.dbConcat(' ','sFname','sLname').' AS fullname
                        FROM HS_Person, HS_Permission_Groups
                        WHERE HS_Person.fUserType = HS_Permission_Groups.xGroup AND fDeleted = 0 ORDER BY sFname, sLname');

/***************************
 * Get Timezone
/****************************/
$systemTz = $db->GetRow( 'SELECT * FROM HS_Settings WHERE sSetting = ? LIMIT 1', array('cHD_TIMEZONE_OVERRIDE') );

$systemTz = trim($systemTz['tValue']);

if( empty($systemTz) )
{
    $systemTz = 'America/New_York'; // Default if not set
}

$tz = $systemTz; // Default for selection

date_default_timezone_set($systemTz);

/***************************
 * Handle Form Submit
/****************************/
$xPerson = '';
$dateStart = '';
$dateEnd = date('m/d/Y', time());
$timeRange = '';

if( isset( $_GET['xPerson'] ) && ! empty( $_GET['xPerson'] ) )
{
    if( isset($_GET['tz']) )
    {
        $tz = $_GET['tz'];
    }

    $xPerson = $_GET['xPerson'];

    $dateStart = $_GET['datestart'];
    $dateEnd = (!empty($_GET['dateend'])) ? $_GET['dateend'] : date('m/d/Y', time());

    $datetimeStart = null;
    if( ! empty($dateStart) )
    {
        $datetimeStart = strtotime($dateStart . ' 00:00:00'); // At start of the day
    }

    $datetimeEnd = null;
    if( ! empty($dateEnd) )
    {
        $datetimeEnd = strtotime($dateEnd . ' 23:59:59'); // At end of the day
    }

    $timeRange = $_GET['timeRange'];

    if( ! is_numeric($timeRange) && is_null($datetimeStart) )
    {
        die('Time range is an illegal number of seconds. <a href="/custom_code/report.php">Try again.</a>');
    }

    if( ! is_null($datetimeStart) )
    {
        $and = "AND HS_Request_History.dtGMTChange >= ? AND HS_Request_History.dtGMTChange <= ?)";
        $parameters = array($datetimeStart, $datetimeEnd, $xPerson, $xPerson);
    } else {
        $and = "AND HS_Request_History.dtGMTChange >= ?)";
        $parameters = array(time() - $timeRange, $xPerson, $xPerson);
    }


    $gmtSecondsAgo = (! is_null($datetimeStart)) ? $datetimeStart : time() - $timeRange;

    $selectedUser = $db->GetRow('SELECT xPerson, sFname, sLname, '.dbConcat(' ','sFname','sLname').' AS fullname FROM HS_Person WHERE xPerson = ?', array($xPerson));

    $report = $db->Execute('SELECT HS_Request_History.xRequest, HS_Request_History.xRequestHistory, HS_Request_History.xPerson, HS_Request_History.dtGMTChange,
                            actioner.sFname AS actioned_by_fname, actioner.sLname AS actioned_by_lname,
                            assignee.sFname AS assigned_to_fname, assignee.sLname AS assigned_to_lname,
                            HS_Request.xPersonAssignedTo AS assigned_to_id,
                            HS_luStatus.sStatus,
                            tLog
                            FROM HS_Request_History
                            JOIN HS_Request ON HS_Request_History.xRequest = HS_Request.xRequest
                            JOIN HS_Person AS actioner ON actioner.xPerson = HS_Request_History.xPerson
                            JOIN HS_Person AS assignee ON assignee.xPerson = HS_Request.xPersonAssignedTo
                            JOIN HS_luStatus ON HS_luStatus.xStatus = HS_Request.xStatus
                            WHERE (tLog != \'\'
                            '.$and.'
                            AND (actioner.xPerson = ?
                            OR assignee.xPerson = ?);', $parameters);

    if( $_GET['output'] === 'csv' )
    {
        $csvData = array(
            // Populate Titles/Headers
            array("Request ID", "Request History ID", "Request Assigned To", "Request Actioned By", "Date Actions")
        );

        // Populate Data
        while( $row = $report->FetchRow() )
        {
            $csvData[] = array(
                $row['xRequest'], $row['xRequestHistory'], $row['assigned_to_fname'].' '.$row['actioned_by_lname'], gmtToTime($row['dtGMTChange'], $tz), unserializeIf($row['tLog'], false)
            );

        }

        header("Content-type: text/csv");
        header("Pragma: public");
        header("Content-Disposition: attachment; filename=report.csv");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header("Content-Transfer-Encoding: binary");
        header("Expires: 0");

        ob_start();
        $out = fopen('php://output', 'w');

        foreach( $csvData as $csvRow )
        {
            fputcsv($out, $csvRow);
        }

        fclose($out);
        $string = ob_get_clean();

        exit($string);
    }

}

?><!doctype html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if IE 9]>    <html class="no-js ie9" lang="en"> <![endif]-->
<!--[if gt IE 9]><!--> 	<html class="no-js" lang="en" itemscope itemtype="http://schema.org/Product"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>Custom Report : HelpSpot</title>
    <style>
        body {
            color: #272727;
            margin: 0;
            padding: 20px;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 18px;
        }
        thead tr td {
            font-weight: bold;
            padding: 8px;
            background-color: #dbeffa;
            border-bottom: 5px solid #aaa;
        }

        tbody tr td
        {
            padding: 8px;
            border-bottom: 1px solid #aaa;
        }

        .odd {
            background-color: #eef6fa;
        }
    </style>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
</head>

<body>

    <div id="top">
        <form action="/custom_code/report.php" method="get">
            <h3>Select a TimeZone </h3>
            <select name="tz">
            <?php
            /*
             * This is user-specific. There are two time-zones checked here - America/LosAngeles (PDT) and
             * Africa\Johannesburg (SAST). This list can/should be updated for your use case.
             * This also checks the system default and displays it if it's not one of the two pre-defined
             * timezones in this case
             */
?>
                <?php if( $systemTz != "America/Los_Angeles" && $systemTz != "Africa/Johannesburg" ):
                    $timezone = new \DateTimeZone($systemTz);?>
                <option value="<?php echo $systemTz ?>">System Default: <?php echo $timezone->getName() ?> (UTC <?php echo $timezone->getOffset( new \DateTime( "now", new \DateTimeZone("UTC") ) )/3600 ?>)</option>
                <?php endif; ?>
                <option value="America/Los_Angeles" <?php if($tz == "America/Los_Angeles"):?>selected<?php endif; ?>>PDT (UTC -7)</option>
                <option value="Africa/Johannesburg" <?php if($tz == "Africa/Johannesburg"):?>selected<?php endif; ?>>SAST (UTC +2)</option>
            </select>
            <h3>Select a User </h3>
            <select name="xPerson">
                <option value="">Select user...</option>
                <?php while( $user = $users->FetchRow() ) : ?>
                <option value="<?=$user['xPerson']?>" <?php if ($xPerson == $user['xPerson']) echo 'selected'; ?>><?=$user['sLname'].', '.$user['sFname']?></option>
                <?php endwhile; ?>
            </select>
            <br /><br />
            <h3>Select Time Range:</h3>
            Starting From:<br />
            <input class="datepicker" name="datestart" value="<?php echo $dateStart; ?>" /> <span style="font-weight:bold; font-size:11px;">* Date fields takes precedence</span>
            <br />Ending On:<br />
            <input class="datepicker" name="dateend" value="<?php echo $dateEnd; ?>" />
            <br /><br />-OR- How far back in time?<br />
            <select name="timeRange">
                <option value="">Select time...</option>
                <option value="86400"   <?php if ($timeRange == 86400)     echo 'selected'; ?>>1 day</option>
                <option value="604800"  <?php if ($timeRange == 604800)    echo 'selected'; ?>>1 week</option>
                <option value="1209600" <?php if ($timeRange == 1209600)   echo 'selected'; ?>>2 weeks</option>
                <option value="1209600" <?php if ($timeRange == 1209600)   echo 'selected'; ?>>2 weeks</option>
                <option value="2419200" <?php if ($timeRange == 2419200)   echo 'selected'; ?>>4 weeks</option>
                <option value="15778500" <?php if ($timeRange == 15778500) echo 'selected'; ?>>6 months</option>
                <option value="31556900" <?php if ($timeRange == 31556900) echo 'selected'; ?>>1 year</option>
            </select>
            <br /><br />
            <input type="submit" name="submit" value="submit" />
        </form>
    </div>

    <div id="bottom">
    <hr />
        <?php if( ! isset($report) ) : ?>
        <h2>No User Selected</h2>
        <?php else: ?>
        <h2>Report for <?=$selectedUser['fullname']?> </h2>
        <p><a href="<?php echo cHOST.'/custom_code/'.basename($_SERVER["SCRIPT_FILENAME"]).'?'.$_SERVER['QUERY_STRING'].'&output=csv'; ?>">Download CSV</a></p>

            <table>
                <thead>
                <tr>
                    <td style="width: 40px;">Request ID</td>
                    <td style="width: 40px;">Request History ID</td>
                    <td style="min-width: 100px;">Request Assigned To</td>
                    <td style="min-width: 100px;">Request Actioned By</td>
                    <td style="min-width: 100px;">Date Actions</td>
                    <td style="min-width: 100px;">Status</td>
                    <td>Action</td>
                </tr>
                </thead>
                <tbody>
                <?php $count = 0; while( $row = $report->FetchRow() ) : ?>
                    <?php
                        if( ! isset($statuses[$row['xRequest']]) )
                        {
                            $statuses[$row['xRequest']] = array();
                        }

                        $statuses[$row['xRequest']][] = parseStatusChange($row['tLog']);
                    ?>
                    <tr class="<?php echo ($count % 2 == 0) ? 'even' : 'odd'; ?>">
                        <td><?=$row['xRequest']?></td>
                        <td><?=$row['xRequestHistory']?></td>
                        <td><?=$row['assigned_to_fname']?> <?=$row['assigned_to_lname']?></td>
                        <td><?=$row['actioned_by_fname']?> <?=$row['actioned_by_lname']?></td>
                        <td><?=gmtToTime($row['dtGMTChange'], $tz)?></td>
                        <td><?=getLastStatus($row['xRequest'])?></td>
                        <td><?=unserializeIf($row['tLog'])?></td>
                    </tr>
                <?php $count++; endwhile; ?>
                </tbody>
            </table>


        <?php endif; ?>
    </div>
<script>
  $(function() {
    $( ".datepicker" ).datepicker();
  });
  </script>
</body>
</html>
