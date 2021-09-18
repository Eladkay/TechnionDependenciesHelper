<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Technion Dependencies Helper - Lookup </title>
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

    <div style="text-align: center;"><h1> Technion Dependency Helper v2</h1></div>
    <h2><b> BETA </b>, report bugs! </h2>
    <p> Updated Sep 18th, 2021 </p>
    <a href="https://github.com/Eladkay/TechnionDependenciesHelper"> GitHub for issues and suggestions </a>
    <p> Enter here a course number, and we will tell you to what courses it is a dependency! </p> <br>
    <form method="post">
        <div class="input-group">
            <label for="course">
                Course number:&nbsp;&nbsp;&nbsp;
            </label>
            <input type="text" class="form-control" name="course" id="course"
                   value="<?php echo strip_tags($_POST['course']); ?>"/>
        </div>


        <div style="text-align: center;"><input type="submit"/></div>
    </form>
    <br>
    <?php
    if (!isset($_POST['course']) || !strlen($_POST["course"])) return;
    echo "<table class='table table-hover caption-top'>";

    echo "<caption>Courses for which the given course is a dependency:</caption>";
    echo "<thead><tr><th>Course Number</th><th>Course Name</th><th>Requirements</th><th>Adjacent (Tzmudim)</th></tr>
     </thead><tbody>";
    $course = trim($_POST["course"]);

    $to_json = array();
    $to_json["course"] = $course;
    $json = json_encode($to_json);
    $url = 'https://eladkay.com:3001/get_dependent_courses/';
    //open connection
    $ch = curl_init($url);

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
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
