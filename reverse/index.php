<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Reverse Technion Dependencies Helper </title>
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

    <div style="text-align: center;"><h1> Reverse Technion Dependency Helper </h1></div>
    <h2><b> BETA </b>, report bugs! </h2>
    <p> Updated Feb 25th, 2021 </p>
    <a href="https://github.com/Eladkay/TechnionDependenciesHelper"> GitHub for issues and suggestions </a>
    <p> Enter here the course numbers you took, separated by spaces, and we will tell you what courses you need in order
        to take CS electives! </p> <br>
    <form method="post">
        <div class="input-group">
            <label for="courses">
                Course numbers, separated by spaces:&nbsp;&nbsp;&nbsp;
            </label>
            <input type="text" class="form-control" name="courses" id="courses"
                   value="<?php echo $_POST['courses']; ?>"/>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="filter" id="filter"
                   value="yes" <?php if (isset($_POST["filter"])) echo "checked"; ?>>
            <label class="form-check-label" for="filter">
                Filter subjects with no dependencies
            </label>
            <br>

            <input class="form-check-input" type="checkbox" name="filter_equiv" id="filter_equiv"
                   value="yes" <?php if (isset($_POST["filter_equiv"])) echo "checked"; ?>>
            <label class="form-check-label" for="filter_equiv">
                Filter subjects that overlap with, are incorporated in, or incorporate courses you have already
                completed
            </label>
        </div>
        <br>

        <div style="text-align: center;"><input type="submit"/></div>
    </form>
    <br>
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
    function foilify($arr)
    {
        $ret = array();
        if (count($arr) == 0) return $ret;
        $induction_hypothesis = foilify(array_slice($arr, 1));
        foreach ($ret[0] as $item) {
            foreach ($induction_hypothesis as $ih) {
                $ret_int = array($item);
                array_push($ret, array_merge($ret_int, $ih));
            }
        }
        return $ret;
    }

    function parse_course_string($course_string)
    {
        $str = str_replace("או", "or", $course_string);
        $str = str_replace("ו-", "and", $str);
        $or_split = explode("or", $str);
        $and_split = explode("and", $str);
        $flag = true;
        foreach ($or_split as $item)
            if (!preg_match("/^\s*\(?.*\)?\s*/", $item)) $flag = false;
        if ($flag) { // or central
            $ret = array();
            foreach ($or_split as $item) {
                $contents = explode("and", str_replace("(", "", str_replace(")", "", $item)));
                $ret_int = array();
                foreach ($contents as $data) array_push($ret_int, trim($data));
                array_push($ret, $ret_int);
            }
            return $ret;
        } else { // and central
            $ret = array();
            foreach ($and_split as $item) {
                $contents = explode("or", str_replace("(", "", str_replace(")", "", $item)));
                $ret_int = array();
                foreach ($contents as $data) array_push($ret_int, trim($data));
                array_push($ret, $ret_int);
            }
            return foilify($ret);
        }
    }

    function get_course($number, $data)
    {
        foreach ($data as $course) {
            if($course["general"]["מספר מקצוע"] == $number) return $course;
        }
        return null;
    }

    function recursively_enumerate($raw, $file, $arr=array())
    {
        if(!isset($raw)) return $arr;
        if(!isset($raw["general"]["מקצועות קדם"])) return $arr;
        array_push($arr, $raw["general"]["מספר מקצוע"]);
        foreach(parse_course_string($raw["general"]["מקצועות קדם"])[0] as $kdam) { // for now
            recursively_enumerate(get_course($kdam, $file), $file, $arr);
        }
        return $arr;
    }

    if (!isset($_POST["courses"])) return;
    $digits = "236";
    $data = json_decode(file_get_contents("courses_202002.json"), true);
    if (!$data) echo "null!";
    echo "<table class='table table-hover caption-top'>";
    echo "<caption>Elective Courses in CS:</caption>";
    echo "<thead><tr><th>Course Number</th><th>Course Name</th><th>Requirements</th><th>Tzmudim</th></tr>
     </thead><tbody>";

    $classifications = "";
    $classifications .= " 123015 "; // chemistry
    $classifications .= "113013 "; // mechanics
    $classifications .= "113014"; // electricity
    $courses_took = explode(" ", trim($_POST["courses"] . $classifications));
    foreach ($data as $course) {
        if (substr($course["general"]["מספר מקצוע"], 0, 3) == $digits) {
            if (isset($_POST["filter"]) && $_POST["filter"] == "yes" && !isset($course["general"]["מקצועות קדם"])) continue;
            if (in_array($course["general"]["מספר מקצוע"], $courses_took)) continue;
            if (isset($_POST["filter_equiv"]) && $_POST["filter_equiv"] == "yes") {
                // assuming these relations are symmetric
                if (isset($course["general"]["מקצועות ללא זיכוי נוסף"]))
                    $no_additional_credit = explode(" ", $course["general"]["מקצועות ללא זיכוי נוסף"]);
                else $no_additional_credit = array();

                if (isset($course["general"]["מקצועות ללא זיכוי נוסף (מוכלים)"]))
                    $incorporated = explode(" ", trim($course["general"]["מקצועות ללא זיכוי נוסף (מוכלים)"]));
                else $incorporated = array();

                if (isset($course["general"]["מקצועות ללא זיכוי נוסף (מכילים)"]))
                    $incorporating = explode(" ", trim($course["general"]["מקצועות ללא זיכוי נוסף (מכילים)"]));
                else $incorporating = array();

                $total_no_additional_credit = array_merge($no_additional_credit, $incorporated, $incorporating);
                $flag = false;
                foreach ($total_no_additional_credit as $included_course) {
                    if (in_array($included_course, $courses_took)) $flag = true;
                }
                if ($flag) continue;
            }
            if (!isset($course["general"]["מקצועות קדם"])) {
                echo "<tr>";
                if (!isset($course["general"]["מקצועות קדם"])) $kdamim = "";
                else $kdamim = recursively_enumerate($course, $data);
                if (!isset($course["general"]["מקצועות צמודים"])) $tzmudim = "";
                else $tzmudim = $course["general"]["מקצועות צמודים"];
                echo "<td>" . $course["general"]["מספר מקצוע"] . "</td><td><p dir=\"rtl\">" . $course["general"]["שם מקצוע"] . "</p></td><td><p dir=\"rtl\">" . $kdamim . "</p></td><td><p dir=\"rtl\">" . $tzmudim . "</p></td>";
                echo "</tr>";
            }
        }
    }
    echo "</tbody></table>";
    ?>
</div>
</body>
</html>
