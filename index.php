<?php

require __DIR__ . '/api-google/vendor/autoload.php';

//AUTENTICACION por Cuenta de Servicio
$pathCredentials = __DIR__ . '/download-from-drive-344514-ac9963e071a3.json';
$downloadPath = __DIR__ . "/downloads";

putenv("GOOGLE_APPLICATION_CREDENTIALS=$pathCredentials");

$client = new Google\Client();
$client->useApplicationDefaultCredentials();
$client->addScope("https://www.googleapis.com/auth/drive");

$folder = 'https://drive.google.com/drive/folders/1v7MA25xW1gwBp8rHIVLuGRCnFTnmH5aV';
$fileId = '1PIBUACgCcMl6mCNXpSwnKNaoWCE6t4Oj';

$service = new Google\Service\Drive($client);

$folderId = '1v7MA25xW1gwBp8rHIVLuGRCnFTnmH5aV';




foreach($list as $element){
  echo "$element->id<br>";
  echo "$element->mimeType<br>";
  echo "$element->name<br>";
}

function downloadOneFile($service, $fileId, $path){
  $response = $service->files->get($fileId, array('alt' => 'media'));
  $content = $response->getBody()->getContents();

  $file = fopen($path, "w+");

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

function downloadAllFiles($service, $idList){
  
}