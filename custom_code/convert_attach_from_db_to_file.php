<?php
/*
*   Notes:
*   This script will convert attachments from being stored on the DB to the file system
*   NOTE: after this runs you also need to run convert_attach_from_db_to_file_clean.php which will actually remove the blob content
*/

//No time limit
set_time_limit(0);

//PATH TO YOUR HELPSPOT CONFIG FILE
include('config.php');

//PATH TO HELPSPOTS ADODB FILES
include(cBASEPATH.'/helpspot/adodb/adodb.inc.php');
include(cBASEPATH.'/helpspot/lib/util.lib.php');
include(cBASEPATH.'/helpspot/lib/api.lib.php'); //has functions used in hsInitSettings

//CREATE DB CONNECTION
hsInitDB();
hsInitSettings();

//FIND ATTACHMENTS
$query = $GLOBALS['DB']->Execute('SELECT HS_Request_History.dtGMTChange, HS_Documents.xDocumentId,HS_Documents.sFilename,HS_Documents_Location.sFileLocation
                                  FROM HS_Request_History,HS_Documents
                                    LEFT OUTER JOIN HS_Documents_Location ON HS_Documents.xDocumentId = HS_Documents_Location.xDocumentId
                                  WHERE HS_Request_History.xDocumentId <> 0 AND HS_Request_History.xDocumentId = HS_Documents.xDocumentId
                                            AND HS_Documents_Location.sFileLocation IS NULL');

//LOOP OVER AND CONVERT TO FILES
while($file = $query->FetchRow()){

    //Check that there isn't already a file location
    if(hs_empty($file['sFileLocation'])){
        //Setup time and directories
        $year = date('Y', $file['dtGMTChange']);
        $month = date('n', $file['dtGMTChange']);
        $day = date('j', $file['dtGMTChange']);

        // Create path to directory location if it doesn't exist
        if(!is_dir(cHD_ATTACHMENT_LOCATION_PATH .'/'. $year .'/'. $month .'/'. $day)){
            if(!is_dir(cHD_ATTACHMENT_LOCATION_PATH .'/'. $year)) @mkdir(cHD_ATTACHMENT_LOCATION_PATH .'/'. $year); //make year folder
            if(!is_dir(cHD_ATTACHMENT_LOCATION_PATH .'/'. $year .'/'. $month)) @mkdir(cHD_ATTACHMENT_LOCATION_PATH .'/'. $year .'/'. $month); //make month folder
            //Don't need is_dir check here since it's done first so we know it isn't
            @mkdir(cHD_ATTACHMENT_LOCATION_PATH .'/'. $year .'/'. $month .'/'. $day); //make day folder
        }

        //hashed file name to prevent "bad guys" from finding it easy should someone put their files in the web root path.
        $ext = explode('.', $file['sFilename']);
        $id = count($ext)-1;
        $extension = ($ext[$id] ? $ext[$id] : 'txt');
        //Use uniqid() in hash to ensure it's unique
        $file_path = '/'. $year .'/'. $month .'/'. $day .'/'. md5($file['sFilename'] . uniqid('helpspot')) .'.'. $extension;

        //Get the body of the file
        $blob = $GLOBALS['DB']->GetOne('SELECT blobFile FROM HS_Documents WHERE xDocumentId = '.$file['xDocumentId']);

        // Try and write files to disk
        $file_write_worked = writeFile(cHD_ATTACHMENT_LOCATION_PATH . $file_path, $blob);

        // Add document to document table
        if($file_write_worked){
            //add path to DB
            $doclocadd = $GLOBALS['DB']->Execute( 'INSERT INTO HS_Documents_Location(xDocumentId,sFileLocation) VALUES(?,?)',
                                                                    array($file['xDocumentId'], $file_path) );

            //remove blob text
            $blob = $GLOBALS['DB']->Execute('UPDATE HS_Documents SET blobFile=null WHERE xDocumentId=?', array($file['xDocumentId']));

            //If the update fails exit
            if(!$blob) die('Database update failed on xDocumentId: '.$file['xDocumentId'].', try restarting script');
        }else{
            echo 'Cannot write to path: '.cHD_ATTACHMENT_LOCATION_PATH . $file_path."\n";exit;
        }
    }

}

?>