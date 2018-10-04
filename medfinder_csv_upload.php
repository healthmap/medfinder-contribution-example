 <?php

 $base_url = "https://api.medfinder.us/api/v1";

 function execCurl($url, $body, $header=false)
 {
     $curl = curl_init();

     curl_setopt($curl, CURLOPT_URL, $url);
     if ($header) {
         curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
     }
     curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
     // curl_setopt($curl, CURLOPT_POST, 1);
     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

     $response = curl_exec($curl);
     $err = curl_error($curl);

     curl_close($curl);
     if ($err) {
         echo "cURL Error #:" . $err;
         return false;
     } else {
         return json_decode($response);
     }
 }

 function credentialsQueryString($email, $password)
 {
     $fields = array(
       "email=".urlencode($email),
       "password=".urlencode($password)
     );
     return join('&', $fields);
 }

function getToken($credentials_query_string)
{
    global $base_url;
    $url = "$base_url/accounts/obtain_token/";

    $result = execCurl($url, $credentials_query_string);

    if ($result) {
        if ($result->non_field_errors) {
            echo "Error: " . $result->non_field_errors[0];
            return false;
        }
    }

    return $result;
}

function submitFile($token, $cFile)
{
    global $base_url;
    $url = "$base_url/medications/csv_import/";

    $body = array('csv_file'=> $cFile);

    $header = array(
      "Authorization: Token $token",
      'Content-Type: multipart/form-data'
    );

    $result = execCurl($url, $body, $header);

    if ($result) {
        if ($result->csv_file) {
            echo "Error: " . $result->csv_file[0];
        }
        if ($result->status) {
            echo $result->status;
        }
    }

    return $result;
}

function postCSVToMedfinderAPI($email, $password, $file_name)
{
    // Get credentials from lgon/password credentials
    $credentials_query_string = credentialsQueryString($email, $password);
    $token_object = getToken($credentials_query_string);

    if (!$token_object) {
        echo "Authentication failed";
        die();
    }

    // Post CSV file
    $file = new CurlFile($file_name, 'text/csv');
    $result = submitFile($token_object->token, $file);
}


// ====================================================================================
// ===================== Medfinder - Post CSV file to API Example =====================
// ====================================================================================
$email = 'example@email.domain';
$password = 'thepassword';
$files = glob("*.csv");

print "\n------------------------------------------------------------\n";
foreach ($files as $file_name) {
    print "Processing: $file_name\n";
    postCSVToMedfinderAPI($email, $password, $file_name);
    print "\n------------------------------------------------------------\n";
}
