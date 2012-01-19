<?php
/*
* By @steveho
*/

require_once('config.inc');
require_once('AWSCloudFrontInvalidator.php');


# Making sure everything is ready
if(!$AWSAccessKeyId || !$AWSSecretAccessKey) {
   print("Please set your AWS credentials in config.inc.\n");
   exit;
}
$args = array_slice($argv, 1);
if(count($args) == 0 || count($args) < 2) {
   print("Oops, missing arguments. You need to provide two arguments.\n");
   print("Usage: php invalidate.php <cloundfront_distribution_id> <object_path_1,object_path_2,...>\n");
   exit;
}

$distributionID = trim($args[0]);
$paths = explode(',', trim($args[1]));

$cloudFront = new AWSCloudFrontInvalidator($AWSAccessKeyId, $AWSSecretAccessKey);

# Make invalidation request
print("Making invalidation request...\n");
$resp = $cloudFront->make_invalidate_request($distributionID, $paths);
$xml = simplexml_load_string($resp['response']);
if($resp['code'] >= 200 && $resp['code'] < 300) {
   print("Request completed successfully:\n");
   print("  ID: {$xml->Id}\n");
   print("  Time: {$xml->CreateTime}\n");
   print("  Status: {$xml->Status}\n");
} else {
   print("Error ({$xml->Error->Code}): {$xml->Error->Message}\n");   
}
print("\n");

# Get list of requests and status
print("Getting status of the last {$xml->MaxItems} invalidation requests...\n");
$resp = $cloudFront->get_invalidation_list($distributionID);
$xml = simplexml_load_string($resp['response']);
if($resp['code'] >= 200 && $resp['code'] < 300) {      
   for($i=0; $i<count($xml->InvalidationSummary); $i++) {
      print("  {$xml->InvalidationSummary[$i]->Id}: {$xml->InvalidationSummary[$i]->Status}\n");
   }
} else {
   print("Error ({$xml->Error->Code}): {$xml->Error->Message}\n");
}

