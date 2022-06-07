<?php

// following is the normal layout of my http log, coding will need changing if different
// 
// 40.77.167.104 - - [30/Aug/2021:02:14:46 +0200] "GET /rotate.php3?ship=Countries/bismarck_firing.jpg HTTP/2" 301 707 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)"

// following is how the log is broken out into its components
// $parts[0] = IP + date and time
//        1    page + protocol
//        2    status + bytes
//        3    -
//        4    sp
//        5    UA

// following needs review to set according to your site volumes per hour. Each item is reported if out of limit
// no report produced if all items in the hour are within limits
// images are tracked by calls to rotate.php. This will need changing if you want to track images

$v2limit = 50; // comments.html - not used anymore, specific to my site
$v0limit = 150; // images 200
$v3limit = 10; // page 403
$v1limit = 50; // page 200
$v4limit = 0; // bad ip - a specific annoying IP
$v5limit = 10; // image 404
$v6limit = 20; // images 403 / 410
$v7limit = 20; // old page - pages with extension of php3 - now phased out
$v8limit = 0; // 429 page - rate limited
$v9limit = 5; // python / curl UA
$v10limit = 0; // HeadlessChrome UA

echo "<html><body><center><h1 >Site Traffic Stats</h1></center>"; // Replace Page Title
$handle = fopen('domain-name-ssl_log','r') or die ('File opening failed'); // Replace log file name
// assumption is the log file is in the same directory as this program

$requestsCount = 0;
$num404 = 0;
$today = date("Y-m-d");
$day = substr($today, 8, 2);

// $_GET retrieves the parameter set in the URL for what is to be reported
// 127.0.0.1/log.php?days=2   // analyses last 2 days only
// 127.0.0.1/log.php?days=99   // analyses the whole log file

$days = $_GET['days'];
if ($days < 1) {
    $days = 1;
}
$day1 = $day - $days;

if ($day1 < 1) {
    $day1 = 30 + $day1;
}



$count = 0;
if($days == 99) {
    $count = 1;
}

// following parses each log line according to the format defined in lines 2-13

while (!feof($handle)) {
    $dd = fgets($handle);
    $parts = explode('"', $dd);
if(!$parts[0]) {
    $parts[0] = " ";
}
if(!$parts[1]) {
    $parts[1] = " ";
}
if(!$parts[2]) {
    $parts[2] = " ";
}
if(!$parts[3]) {
    $parts[3] = " ";
}
if(!$parts[4]) {
    $parts[4] = " ";
}
if(!$parts[5]) {
    $parts[5] = " ";
}

    list($ip, $hyp, $hyp2, $date, $rest) = explode(" ", $parts[0]);
    if(!$date) {
    $date = " ";
}
    $time = explode(":", $date);
    $date = substr($date, 1);
    $day = substr($date, 0, 2);

    if ($count != 1) {
        
        if ($day == $day1) {
            $count = 1;
        }
    } else {

    list($nulls, $statusCode, $bytes) = explode(" ", $parts[2]);
            
    // following IF ignores traffic from search engines etc
    
    if (stristr($parts[5], "bingbot") or stristr($parts[5], "Google") or stristr($parts[5], "duckduckgo") or stristr($parts[5], "yahoo.com") or stristr($parts[5], "proximic") or stristr($parts[5], "grapeshot")  or stristr($parts[5], "admantx.com")) {
        
    } else {
        
        // following code accumulates numbers for each item being reported

        if (stristr($parts[5], "python") or stristr($parts[5], "curl") or stristr($parts[5], "go-http")) {
            $result[$day][$time[1]][9]++;

        } else {
            // this tracks images
        if (stristr($parts[1], "rotate.php?") and $statusCode == 200) {
            $result[$day][$time[1]][0]++;
        } else {
                        // this tracks images
        if (stristr($parts[1], "rotate.php?") and $statusCode == 404) {
            $result[$day][$time[1]][5]++;
        } else {      
        if ((stristr($parts[1], ".php ")  or stristr($parts[1], ".php?fbclid") or (stristr($parts[1], "GET / "))) and $statusCode == 200) {
            $result[$day][$time[1]][1]++;
 //       } else {
 //           if (stristr($parts[1], "comments.html") and $statusCode == 410) {
 //           $result[$day][$time[1]][2]++;  
        } else {
            if ((stristr($parts[1], ".php ") or stristr($parts[1], ".php?fbclid") or (stristr($parts[1], "GET / "))) and ($statusCode == 403 or $statusCode == 410)) {
            $result[$day][$time[1]][3]++;
        } else {
            if ((stristr($parts[1], ".php ")   or stristr($parts[1], ".php?fbclid") or (stristr($parts[1], "GET / "))) and $statusCode == 429) {
            $result[$day][$time[1]][8]++;
        } else {
            if (stristr($parts[1], ".php3 ")) {
            $result[$day][$time[1]][7]++;
        } else {           
                        // this tracks images
        if ((stristr($parts[1], "rotate.php?") or stristr($parts[1], "rotate.php3?")) and ($statusCode == 403 or $statusCode == 410)) {
            $result[$day][$time[1]][6]++;
        }
        }
        }       
        }
//        }
        }
        }
        }
        }
        if (stristr($ip, "130.255.")) {
            $result[$day][$time[1]][4]++;
        }

        if (stristr($parts[5], "headlessChrome")) {
            $result[$day][$time[1]][10]++;
        }
    
}
    }
}
fclose($handle);
$lastday = 0;
print "<table style=\"width:80%; margin-left: auto; margin-right: auto; text-align: center; padding: 15px;\">";
        print "<tr><td>Day</td><td>Time</td><td>200 Page - <font color=red>$v1limit</font></td><td>200 Image - <font color=red>$v0limit</font></td><td> 403 Page - <font color=red>$v3limit</font></td><td> 404 Image - <font color=red>$v5limit</font></td><td> 403/410 Image - <font color=red>$v6limit</font></td><td> PHP3 Page - <font color=red>$v7limit</font></td><td> 429 - <font color=red>$v8limit</font></td><td>  Bad IP - <font color=red>$v4limit</font></td><td>  Curl / Python / Go-http - <font color=red>$v9limit</font></td><td>  HeadLess - <font color=red>$v10limit</font></td></tr>";
                if (!$result) {
            echo "<tr><td><br><br>No results</td></tr>";
        } else {
foreach($result as $key=>$val){ 

    foreach($val as $k=>$v){ 
        
        // $ratio is the ratio between images pulled and php requests. If it is outside normal limits worth investigating
        
        $ratio = 0;
        if($v[1] > 0) {
        $ratio = $v[0] / $v[1];
        }

        
        if ($ratio > 8 or $ratio < 2.3 or $v[3] > $v3limit or $v[1] > $v1limit or $v[4] > $v4limit or $v[5] > $v5limit or $v[6] > $v6limit or $v[7] > $v7limit or $v[8] > $v8limit or $v[9] > $v9limit or $v[10] > $v10limit){
            if($lastday == $key) {
                $pkey = "";
            } else {
                $lastday = $key;
                $pkey = $key;
            }
//            if ($v[2] > $v2limit) {
//                $v2c = "red";
 //           } else {
 //               $v2c = "black";
 //           }
            
            // normal results are in black, problematic in red
            
            if ($ratio > 8 or $ratio < 3) {
                $v0c = "red";
            } else {
                $v0c = "black";
            }
            if ($v[3] > $v3limit) {
                $v3c = "red";
            } else {
                $v3c = "black";
            }
            if ($v[1] > $v1limit) {
                $v1c = "red";
            } else {
                $v1c = "black";
            }
            if ($v[4] > $v4limit) {
                $v4c = "red";
            } else {
                $v4c = "black";
            }
            if ($v[5] > $v5limit) {
                $v5c = "red";
            } else {
                $v5c = "black";
            }
            if ($v[6] > $v6limit) {
                $v6c = "red";
            } else {
                $v6c = "black";
            }
            if ($v[7] > $v7limit) {
                $v7c = "red";
            } else {
                $v7c = "black";
            }
            if ($v[8] > $v8limit) {
                $v8c = "red";
            } else {
                $v8c = "black";
            }
            if ($v[9] > $v9limit) {
                $v9c = "red";
            } else {
                $v9c = "black";
            }
            if ($v[10] > $v10limit) {
                $v10c = "red";
            } else {
                $v10c = "black";
            }
        printf("<tr style=\"padding: 15px;\"><td><font color=black>$pkey</font></td><td><font color=green>$k</font></td><td> <font color=$v1c>$v[1]</font></td><td><font color=$v0c>$v[0] - %1.2f</font></td><td><font color=$v3c> $v[3]</font></td><td><font color=$v5c> $v[5]</font></td><td><font color=$v6c> $v[6]</font></td><td><font color=$v7c> $v[7]</font></td><td><font color=$v8c> $v[8]</font></td><td><font color=$v4c> $v[4]</font></td><td><font color=$v9c> $v[9]</font></td><td><font color=$v10c> $v[10]</font></td></tr>", $ratio);
  
        }
    }
}
        }

print "</table></body></html>";




?>
