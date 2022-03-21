<?php

require __DIR__ . '/api-google/vendor/autoload.php';
date_default_timezone_set('America/Asuncion');

//AUTENTICACION por Cuenta de Servicio
$pathCredentials = __DIR__ . '/download-from-drive-344514-ac9963e071a3.json';
$downloadPath = __DIR__ . "/downloads";

putenv("GOOGLE_APPLICATION_CREDENTIALS=$pathCredentials");

$client = new Google\Client();
$client->useApplicationDefaultCredentials();
$client->addScope("https://www.googleapis.com/auth/drive");

$service = new Google\Service\Drive($client);

$dowloadPath = __DIR__.'/downloads';
$csvPath = __DIR__.'/links.csv';

//test
// $folderLink = 'https://drive.google.com/drive/folders/1v7MA25xW1gwBp8rHIVLuGRCnFTnmH5aV?usp=sharing';
// $folderLink = 'https://drive.google.com/drive/folders/1oV-qfNcAfP5WEQCiXlzBzbdhoTHAreQw?usp=sharing';

// $fileId = '1qck88s3EJRkjl6W-7ezMe_pC-VyLj0iy';
// $result = $service->files->get($fileId, array('fields' => 'fileExtension, name'));
// $name = $result['name'];
// $fileExtension = $result['fileExtension'];
// echo  $fileExtension;
// $name = str_replace(".$fileExtension", '', $name);
// echo $name;

// die('fin');

displayEchoWhileExecuting();

$links = getLinksFromCsv($csvPath);

if ($links === null){
  die("Error al leer csv");
}

foreach($links as $link){
  if(!strpos($link, 'folders/')){
    echo "<br>Link: $link<br>";
    $fileId = getFileId($link);
    $result = $service->files->get($fileId, array('fields' => 'fileExtension, name'));
    $name = $result['name'];
    $fileExtension = $result['fileExtension'];
    downloadOneFile($service, $fileId, $downloadPath, $fileExtension, time());
    writeOnFolderNames($name, $fileId);
    
  }else{
    $folderId = getFolderId($link);
    $list = listFilesFromFolder($service, $folderId);
    $folderName = '';
    
    if(count($list)>0){
      $folderName = getFolderName($service, $folderId);
      writeOnFolderNames($folderName, $folderId);  
    }
    
    echo "<br>Link: $link: $folderName<br>";
    downloadListOfFiles($service, $list, $downloadPath, $folderId); 
  }  
  
}

function downloadOneFile($service, $fileId, $path, $fileExtension, $name){
  if(!imageType($fileExtension)){
    return;
  }
  
  $response = $service->files->get($fileId, array('alt' => 'media'));
  $content = $response->getBody()->getContents();
  
  $file = fopen("$path/$name.$fileExtension", "w+");

  fwrite($file, $content);
  fclose($file);
  echo "$fileId <br>";
}

function listFilesFromFolder($service, $folderId){
  $parameters = array(
    'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
    'q' => "'".$folderId."' in parents"
  );
  
  $list = $service->files->listFiles($parameters);
  
  return $list;
}

function downloadListOfFiles($service, $list, $downloadPath, $folderId){
  
  $length = count($list);
  if($length <= 0) {
    echo "VACIO<br>"; 
    return;
  };
  
  $name = time();
  mkdir ("$downloadPath/$folderId");
  $path = "$downloadPath/$folderId";
  
  for($i = 0; $i < $length; $i++){
    downloadOneFile($service, $list[$i]['id'], $path, $list[$i]['fileExtension'], $name+$i);
  }
  
}

function getFolderId($folderLink){
  $needle = 'folders/';
  $folderId = substr($folderLink, stripos($folderLink, $needle) + strlen($needle));
  $folderId =  substr($folderId, 0, stripos($folderId, '?'));
  return $folderId;
}

function displayEchoWhileExecuting(){
  ob_implicit_flush(true);
  ob_end_flush();
}

function getLinksFromCsv($csvPath){
  
  $csv = fopen($csvPath, "r");
  $links = fgetcsv($csv);
  fclose($csv);
  return $links;
    
}

function getFolderName($service, $folderId){
  $result = $service->files->get($folderId);
  $name = $result->getName();
  return $name;
}

function writeOnFolderNames($folderName, $folderId){
  $file = __DIR__.'/FolderNames.txt';
  file_put_contents($file, "$folderName ; $folderId\n", FILE_APPEND);
}

function getFileId($fileLink){
  $needle = 'file/d/';
  $fileId = substr($fileLink, stripos($fileLink, $needle) + strlen($needle));
  $fileId =  substr($fileId, 0, stripos($fileId, '/view'));
  return $fileId;
}

function imageType($fileExtension){
  $fileExtension = strtolower($fileExtension);
  $extensions = array('jpg','jpeg','png','heic');
  if(in_array($fileExtension, $extensions)){
    return true;
  }else{
    return false;
  }
  
}