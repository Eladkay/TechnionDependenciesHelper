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

    <div style="text-align: center;"><h1> Technion Dependency Helper v2 </h1></div>
    <h2><b> BETA </b>, report bugs! </h2>
    <p> Updated Sep 18th, 2021 </p>
    <p> <strong>Now available!</strong> <a href="lookup">Lookup</a> and <a href="tree">Deps. Tree</a></p>
    <a href="https://github.com/Eladkay/TechnionDependenciesHelper"> GitHub for issues and suggestions </a>
    <p> Enter here the course numbers you took, in any format, and we will tell you what courses you can
        take! </p> <br>
    <form method="post">
        <div class="input-group">
            <label for="courses">
                String containing course numbers:&nbsp;&nbsp;&nbsp;
            </label>
            <input type="text" class="form-control" name="courses" id="courses"
                   value="<?php echo htmlspecialchars(strip_tags($_POST['courses'])); ?>"/>
        </div>
        <br>
        <div class="input-group">
            <label for="digits">
                Optionally, filter only courses whose course numbers contain this string: &nbsp;&nbsp;&nbsp;
            </label>
            <input type="text" class="form-control" name="digits" id="digits" value="<?php echo htmlspecialchars(strip_tags($_POST['digits'])); ?>"/>
            <br>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="filter" id="filter"
                   value="yes" checked>
            <label class="form-check-label" for="filter">
                Don't include subjects with no dependencies
            </label>
            <br>

            <input class="form-check-input" type="checkbox" name="filter_equiv" id="filter_equiv"
                   value="yes" checked>
            <label class="form-check-label" for="filter_equiv">
                Don't include subjects that overlap with, are incorporated in, or incorporate courses you have already
                completed
            </label>
        </div>

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
    if (!isset($_POST['courses']) || !strlen($_POST["courses"])) return;
    echo "<table class='table table-hover caption-top'>";

    echo "<caption>Courses you can take (with the correct tzmudim):</caption>";
    echo "<thead><tr><th>Course Number</th><th>Course Name</th><th>Requirements</th><th>Adjacent (Tzmudim)</th></tr>
     </thead><tbody>";
    $courses_took = trim($_POST["courses"]);
    $matches = array();
    preg_match_all("(\\d{5,6})", $courses_took, $matches);
    $list = [];
    $match = $matches[0];
    foreach ($match as $cn) {
        if (strlen($cn) == 6)
            $list[] = $cn;
        else $list[] = "0" . $cn;
    }
    $to_json = array();
    $to_json["courses"] = $list;
    $to_json["exclude_no_deps"] = isset($_POST["filter"]) && $_POST["filter"] == "yes";
    $to_json["exclude_contained"] = isset($_POST["filter_equiv"]) && $_POST["filter_equiv"] == "yes";
    if (isset($_POST["phys1"]) && $_POST["phys1"] == "yes") $to_json["phys_mech"] = true;
    if (isset($_POST["phys2"]) && $_POST["phys2"] == "yes") $to_json["phys_elec"] = true;
    if (isset($_POST["chem"]) && $_POST["chem"] == "yes") $to_json["chem"] = true;
    if (isset($_POST["digits"])) $to_json["filter"] = $_POST["digits"];
    $json = json_encode($to_json);
    $url = 'https://127.0.0.1:3001/get_possible_courses/';

    //open connection
    $ch = curl_init($url);

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

    //So that curl_exec returns the contents of the cURL; rather than echoing it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //execute post
    $result = curl_exec($ch);

    curl_close($ch);

    foreach (json_decode($result) as $course) {
        $to_ugified = function($val) {
            return "<a href='https://ug3.technion.ac.il/rishum/course/$val[0]/202101'>$val[0]</a>";
        };
        $to_ugified2 = function($val) {
            if($val == "") return "";
            return "<a href='https://ug3.technion.ac.il/rishum/course/$val/202101'>$val</a>";
        };
        $preqs = preg_replace("/\s+/", "", $course->{"preqs"});
        $preqs = preg_replace('/[\x00-\x1F\x7F]/u', '', $preqs);
        $preqs = preg_replace_callback("/(\d{5,6})/", $to_ugified, $preqs);
        $preqs = preg_replace("(או)", " or ", $preqs);
        $preqs = preg_replace("(ו-)", " and ", $preqs);

        $adjs = preg_replace_callback("/(\d{5,6})/", $to_ugified, $course->{"adjs"});
        $adjs = preg_replace("(\s)", " or ", $adjs);

        echo "<tr>";
        echo "<td>" . $to_ugified2($course->{"number"}) . "</td><td><p dir=\"rtl\">" . $course->{"name"} .
            "</p></td><td>$preqs</td><td>$adjs</td>";
        echo "</tr>";

    }
    echo "</tbody></table>";
    ?>
</div>
</body>
</html>
