<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Technion Dependencies Helper </title>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW"
            crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
<div class="container">

    <div style="text-align: center;"><h1> Technion Dependency Helper </h1></div>
    <h2><b> BETA </b>, report bugs! </h2>
    <p> Updated Sep 18th, 2021 </p>
    <a href="https://github.com/Eladkay/TechnionDependenciesHelper"> GitHub for issues and suggestions </a>
    <p> Enter here the course numbers you took, in any format, and we will tell you what courses you can
        take! </p> <br>
    <form method="post">
        <div class="input-group">
            <label for="courses">
                String containing course numbers:&nbsp;&nbsp;&nbsp;
            </label>
            <input type="text" class="form-control" name="courses" id="courses"
                   value="<?php echo $_POST['courses']; ?>"/>
        </div>
        <br>
        <div class="input-group">
            <label for="digits">
                Optionally, filter only courses whose course numbers contain this string: &nbsp;&nbsp;&nbsp;
            </label>
            <input type="text" class="form-control" name="digits" id="digits" value="<?php echo $_POST['digits']; ?>"/>
            <br>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="filter" id="filter"
                   value="yes" checked>
            <label class="form-check-label" for="filter">
                Filter subjects with no dependencies
            </label>
            <br>

            <input class="form-check-input" type="checkbox" name="filter_equiv" id="filter_equiv"
                   value="yes" checked>
            <label class="form-check-label" for="filter_equiv">
                Filter subjects that overlap with, are incorporated in, or incorporate courses you have already completed
            </label>
        </div>

        <!-- <input type="submit" name="bidusa"> <-- Bidusa Button </input> <br> <br> !-->

        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="chem" id="chem"
                   value="yes" <?php if (isset($_POST["chem"])) echo "checked"; ?>>
            <label class="form-check-label" for="chem">
                Include courses that require chemistry classification
            </label>
            <br>

            <input class="form-check-input" type="checkbox" name="phys1" id="phys1"
                   value="yes" <?php if (isset($_POST["phys1"])) echo "checked"; ?>>
            <label class="form-check-label" for="phys1">
                Include courses that require physics classification for mechanics
            </label>
            <br>

            <input class="form-check-input" type="checkbox" name="phys2" id="phys2"
                   value="yes" <?php if (isset($_POST["phys2"])) echo "checked"; ?>>
            <label class="form-check-label" for="phys2">
                Include courses that require physics classification for electricity
            </label>
        </div>

        <br>

        <div style="text-align: center;"><input type="submit"/></div>
    </form>
    <br>
    <?php
    if(!isset($_POST['courses'])) return;
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
    echo "<table class='table table-hover caption-top'>";

    echo "<caption>Courses you can take (with the correct tzmudim):</caption>";
    echo "<thead><tr><th>Course Number</th><th>Course Name</th><th>Requirements</th><th>Tzmudim</th></tr>
     </thead><tbody>";
    $courses_took = trim($_POST["courses"]);
    $matches = array();
    preg_match_all("(\\d{5,6})", $courses_took, $matches);
    $list = [];
    $match = $matches[0];
    foreach ($match as $cn) {
        if(strlen($cn) == 6)
            $list[] = $cn;
        else $list[] = "0".$cn;
    }
    $to_json = array();
    $to_json["courses"] = $list;
    $json = json_encode($to_json);
    echo $json;
    $url = 'https://eladkay.com:3001/get_possible_courses/';
    $fields_string = http_build_query($json);

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

    //So that curl_exec returns the contents of the cURL; rather than echoing it
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

    //execute post
    $result = curl_exec($ch);
    echo $result;
//    foreach ($data as $course) {
//            if (!isset($course["general"]["מקצועות קדם"]) || check_kdamim($course["general"]["מקצועות קדם"], $courses_took)) {
//                echo "<tr>";
//                if (!isset($course["general"]["מקצועות קדם"])) $kdamim = "";
//                else $kdamim = $course["general"]["מקצועות קדם"];
//                if (!isset($course["general"]["מקצועות צמודים"])) $tzmudim = "";
//                else $tzmudim = $course["general"]["מקצועות צמודים"];
//                echo "<td>" . $course["general"]["מספר מקצוע"] . "</td><td><p dir=\"rtl\">" . $course["general"]["שם מקצוע"] . "</p></td><td><p dir=\"rtl\">" . $kdamim . "</p></td><td><p dir=\"rtl\">" . $tzmudim . "</p></td>";
//                echo "</tr>";
//            }
//
//    }
    echo "</tbody></table>";
    ?>
</div>
</body>
</html>
