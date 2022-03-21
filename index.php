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
// $id = '1PIBUACgCcMl6mCNXpSwnKNaoWCE6t4Oj';
$id = '1v7MA25xW1gwBp8rHIVLuGRCnFTnmH5aV';

// displayEchoWhileExecuting();

// $links = getLinksFromCsv($csvPath);

// foreach($links as $link){
  
//   echo "<br>Link: $link<br>";
  
//   $folderId = getFolderId($link);

//   $list = listFilesFromFolder($service, $folderId);

//   downloadListOfFiles($service, $list, $downloadPath, $folderId);
  
// }





function downloadOneFile($service, $fileId, $path, $fileExtension, $name){
  $response = $service->files->get($fileId, array('alt' => 'media'));
  $content = $response->getBody()->getContents();

  $file = fopen("$path/$name.$fileExtension", "w+");

  fwrite($file, $content);
  fclose($file);
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
    echo "{$list[$i]['id']} <br>";

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

function getFolderName($service, $id){
  $result = $service->files->get($id);
  $name = $result->getName();
  return $name;
}