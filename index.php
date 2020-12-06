<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoomTown Assesment</title>
</head>

<body>
    <h1>1. Output Data</h1>
    <a href='index.php?runOutput=true'>Click here to run function</a><br>
    <h1>2. Perform Verifications</h1>
    <a href='index.php?runVerifications=true'>Click here to run function</a><br>


    <?php
        // api endpoint for the initial call
        $initialEndpoint = "https://api.github.com/orgs/BoomTownROI";
        $endpointNames = [];

        // function: creates a cURL object and makes a call to an API endpoint
            // input: API endpoint that we are trying to reach
            // output: returns an array that houses the body and the headers of the api response
        function callAPI($endpoint) {
            $returnArr = []; // initialize an Array for return
            $ch = curl_init();

            // set up for cURL object
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            // return the response as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Follow any redirects that the url has
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            // enable response headers
            curl_setopt($ch, CURLOPT_HEADER, true);
            // enable request headers 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'User-Agent: AlexBent564',
                'Content-Type: application/json',
            ));

            // execute the cURL object and separate the headers from the body of the response
            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $full_header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);

            // close the connection to save server resources
            curl_close($ch);

            // turn the header into an array
            $headers = explode(" ", $full_header);

            array_push($returnArr, $body);
            array_push($returnArr, $headers);

            // execute the api call using cURL
            return $returnArr;
        }

        
        // function: uses an endpoint to make an api call and parses that response for more endpoints
            // input: none
            // output: nothing. adds found endpoints to an array
        function gatherEndpoints() {            
            global $initialEndpoint;
            global $endpointNames;

            // call api with the passed in endpoint
            $response = callAPI($initialEndpoint);
            $body = $response[0];
            $readable = json_decode($body);
            foreach($readable as $value) {
                if(strpos($value, $initialEndpoint) !== false) {
                    // make sure to strip any unwanted items from the string
                        // need to check for it first so it doesnt effect the other endpoints that dont contain "{"
                    if(strpos($value, "{") !== false) {
                        $value = substr($value, 0, strpos($value, "{"));
                    }

                    // add the endpoints to the array for parsing
                    array_push($endpointNames, $value);                    
                }
            }
        }

        // function: Traverses the endpointNames array to gather all the necessary information
            // input: none
            // output: none
        function followEndpoints() {
            global $endpointNames;
            
            // for loop to loop through the list of gathered api endpoints
            foreach($endpointNames as $endpoint) {
                $response = callAPI($endpoint."?per_page=100");
                $body = $response[0];
                $headers = $response[1];

                // create a json iterator object to iterate through multi hierarchy jsons
                $jsonIterator = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator(json_decode($body, TRUE)),
                    RecursiveIteratorIterator::SELF_FIRST);

                // check to make sure we got the right status
                if($headers[1] == 200) {

                    // iterate through multi layered jsons with the iterator
                    foreach ($jsonIterator as $key => $val) {
                        if(!is_array($val) && $key == "id") {                    
                            displayData($endpoint, $key, $val, $headers[1], "Connection Successful");                
                        }
                    }
                }
                else {
                    displayData($endpoint, null, null, $headers[1], "Connection Unsuccessful");
                }
            }
        }

        // function: takes a series of inputs and prints them to the page
            // input: endpoint - api endpoint, key - The key that we are looking for, value - the value from the key,
            //        status - the connection code, string - whatever string that needs to be put in
            // output: none. prints to page
        function displayData($endpoint, $key, $value, $status, $string) {
            print "<strong>API Endpoint: </strong>".$endpoint."<strong> Connection Status: </strong>".$status." "
                                                                    .$string." <strong>".$key."</strong> ".$value."</br>";
        }

        // function: verifies that the 'updated_at' variable is later than the 'created_at' variable at the top level
            // input: none
            // output: prints to the screen whether or not the object was updated
        function verifyUpdate() {
            global $initialEndpoint;

            // call the top level to get the necessary info
            $response = callAPI($initialEndpoint);
            $body = $response[0];
            $readable = json_decode($body);

            // find the time and convert to timestamp
            $createdTime = strtotime($readable->created_at);
            $updatedTime = strtotime($readable->updated_at);;

            // conditional to verify which even happened first
            if($createdTime < $updatedTime) {
                print "<strong>Verify 'updated_at' is later than 'created_at'</strong><br>";
                print "Object was created at ".$readable->created_at."<br>";
                print "Object was updated at ".$readable->updated_at."<br>";
                print "The object was updated correctly<br>";
            }
            else {
                print "<strong>Verify 'updated_at' is later than 'created_at'</strong><br>";
                print "Object was created at ".$readable->created_at."<br>";
                print "Object was updated at ".$readable->updated_at."<br>";
                print "The object can not be updated before being created<br>";
            }
        }

        // function: compare the 'public_repos' count against the repositories array returned from following the 'repos_url', verifying that the counts match.
            // input: none
            // output: prints to the page whether or not the counts align 
        function checkRepos() {
            global $initialEndpoint;

            // make the api call to the initial endpoint to get 'public_repos' value
            $initialResponse = callAPI($initialEndpoint);
            $initialBody = $initialResponse[0];
            $initialReadable = json_decode($initialBody);

            // make the call to the repos url and adding 'per_page=100' to get more than 30 objects
            $urlResponse = callAPI("https://api.github.com/orgs/BoomTownROI/repos?per_page=100");
            $urlBody = $urlResponse[0];
            $urlReadable = json_decode($urlBody);

            // checks to see if the values are the same
            if($initialReadable->public_repos == sizeof($urlReadable)) {
                print "<strong>Verify 'public_repos' count vs amount of repositories under 'repos_url</strong><br>";
                print "Public repos value is ".$initialReadable->public_repos."<br>";
                print "Amount of urls in array is ".sizeof($urlReadable)."<br>";
                print "The 'public_repos' count and the amount of repositories are the same<br>";
            }
            else {
                print "<strong>'public_repos' count vs amount of repositories under 'repos_url</strong><br>";
                print "Public repos value is ".$initialReadable->public_repos."<br>";
                print "Amount of urls in array is ".sizeof($urlReadable)."<br>";
                print "The 'public_repos' count and the amount of repositories are NOT the same<br>";
            }
        }

        // function: aggregate function that is used to run output functions with a button click
            // input: none
            // output: none
        function runOutput() {
            gatherEndpoints();
            followEndpoints();
        }

        // function: aggregate function that is used to run verification functions with a button click
            // input: none
            // output: none
        function runVerifications() {
            verifyUpdate();
            checkRepos();
        }

        // listener for running the functions from the html
        if (isset($_GET['runOutput'])) {
            runOutput();
        }
        else if(isset($_GET['runVerifications'])) {
            runVerifications();
        }      
    ?>
</body>
</html>