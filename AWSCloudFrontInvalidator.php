<?php
/*
* By @steveho
*/

class AWSCloudFrontInvalidator {
   private $_awsAccessKeyId = null;
   private $_awsSecretAccessKey = null;
   
   public function __construct($accessKeyId, $secretAccessKey)
   {
      $this->_awsAccessKeyId = $accessKeyId;
      $this->_awsSecretAccessKey = $secretAccessKey;
   }
   
   private function _makeRequest($type = 'GET', $resource, $body = null) 
   {
      $type = strtoupper($type);
      if($type == 'POST') $request  = "POST {$resource} HTTP/1.0\r\n";
      else $request  = "GET {$resource} HTTP/1.0\r\n";

      $date = gmdate("D, d M Y G:i:s T");
      $signature = base64_encode(hash_hmac("sha1", utf8_encode($date), utf8_encode($this->_awsSecretAccessKey), true));

      $request .= "Host: cloudfront.amazonaws.com\r\n";
      $request .= "Content-Type: text/xml\r\n";
      if($type == 'POST') $request .= "Content-Length: " . strlen($body) . "\r\n";
      $request .= "Date: $date\r\n";
      $request .= "Authorization: AWS {$this->_awsAccessKeyId}:{$signature}\r\n";
      $request .= "\r\n";
      if($type == 'POST') $request .= $body;

      $response = '';
      if ($socket = @fsockopen('ssl://cloudfront.amazonaws.com', 443, $errno, $errstr, 10)) {
          fwrite($socket, $request);
          while (!feof($socket)) {
              $response .= fgets($socket, 1160);
          }
          fclose($socket);
          list($other, $responseBody) = explode("\r\n\r\n", $response, 2);
          $other = preg_split("/\r\n|\n|\r/", $other);
          list($protocol, $code, $text) = explode(' ', trim(array_shift($other)), 3);
                    
      } else {
          throw new Exception ("Unable to establish connection to host cloudfront.amazonaws.com: $errstr");
      }
      
      return array('code'=>$code, 'response'=>$responseBody);
   }
   
   public function make_invalidate_request($distributionID, array $paths)
   {
      $pathStr = "<Path>" . implode("</Path><Path>", $paths) . "</Path>";
      $patch = "patch." . date("YmdHis");      
      $body = "<?xml version='1.0' encoding='UTF-8'?><InvalidationBatch>{$pathStr}<CallerReference>{$patch}</CallerReference></InvalidationBatch>";

      return $this->_makeRequest('POST', "/2010-11-01/distribution/{$distributionID}/invalidation", $body);
   }
   
   public function get_invalidation_list($distributionID, $max=10)
   {
      return $this->_makeRequest('GET', "/2010-11-01/distribution/{$distributionID}/invalidation?MaxItems={$max}");
   }
   
}

