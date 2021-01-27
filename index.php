
<html>
<head>
<title> Technion Dependencies Helper </title>
<style>
table, th, td {
	border: 1px solid black;
	border-collapse: collapse;
}
</style>
</head>
<body>
<center> <h1> Technion Dependency Helper </h1> </center>
<h2> <b> BETA </b>, report bugs! </h2>
<p> Updated Jan 27th, 2021 </p>
<a href="https://github.com/Eladkay/TechnionDependenciesHelper"> GitHub for issues and suggestions </a>
<p> Enter here the course numbers you took, separated by spaces, and we will tell you what courses you can take! </p>
<form method="post" action="index.php">
<input type="textbox" name="courses" value="<?php echo $_POST['courses']; ?>"/>
<p> Optionally, the first three digits for course numbers to look through (for example, 236 for CS electives like 236363 Database Systems), defaults to 236:
<input type="textbox" name="digits" value="<?php echo $_POST['digits']; ?>"/> </p>
<input type="checkbox" name="filter" value="yes" <?php if(isset($_POST["filter"])) echo "checked"; ?>> Filter subjects with no dependencies <br>
<!-- <input type="submit" name="bidusa"> <-- Bidusa Button </input> <br> <br> !-->
<input type="checkbox" name="chem" value="yes" <?php if(isset($_POST["chem"])) echo "checked"; ?>> Chemistry classification <br>
<input type="checkbox" name="phys1" value="yes" <?php if(isset($_POST["phys1"])) echo "checked"; ?>> Physics classification for mechanics <br>
<input type="checkbox" name="phys2" value="yes" <?php if(isset($_POST["phys2"])) echo "checked"; ?>> Physics classification for electricity <br>

<input type="submit"/>
</form>
<br>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
function foilify($arr) {
	$ret = array();
	if(count($arr) == 0) return $ret;
	$induction_hypothesis = foilify(array_slice($arr, 1));
	foreach($ret[0] as $item) {
		foreach($induction_hypothesis as $ih) {
			$ret_int = array($item);
			array_push($ret, array_merge($ret_int, $ih));
		}
	}
	return $ret;
}
function parse_course_string($course_string) {
	$str = str_replace("או", "or", $course_string);
	$str = str_replace("ו-", "and", $str);
	$or_split = explode("or", $str);
	$and_split = explode("and", $str);
	$flag = true;
	foreach($or_split as $item)
		if(!preg_match("/^\s*\(?.*\)?\s*/", $item)) $flag = false;
	if($flag) { // or central
		$ret = array();
		foreach($or_split as $item) {
			$contents = explode("and", str_replace("(", "", str_replace(")","",$item)));
			$ret_int = array();
			foreach($contents as $data) array_push($ret_int, trim($data));
			array_push($ret, $ret_int);
		}
		return $ret;
	} else { // and central
		$ret = array();
		foreach($and_split as $item) {
                        $contents = explode("or", str_replace("(", "", str_replace(")","",$item)));
                        $ret_int = array();
                        foreach($contents as $data) array_push($ret_int, trim($data));
			array_push($ret, $ret_int);
                }
		return foilify($ret);
	}
}
function check_kdamim($course_string, $user_string, $course_name_for_debug) {
	$courses_took = explode(" ", $user_string);
	$parsed = parse_course_string($course_string);
	if(count($parsed) == 0) return true;
	$flag = false;
	foreach($parsed as $item_set) {
		$flag2 = true;
		foreach($item_set as $item) {
			if(!in_array(trim($item), $courses_took) && !in_array(substr($item, 0, 6), $courses_took)) {
				$flag2 = false;
			}
		}
		if($flag2) $flag = true;
	}
	return $flag;
}
if(isset($_POST["bidusa"])) {
	$num = rand(10, 100);
	for($i = 2; $i < $num; $i++) {
		$flag = true;
		for($j = 2; $j < $i; $j++)
			if($i % $j == 0) $flag = false;
		if($flag) echo $i. "<br>";
	}
	echo "All in O(n)!";
	return;
}
if(!isset($_POST["courses"])) return;
$digits = "236";
if(isset($_POST["digits"]) && (strlen($_POST["digits"])==3 || $_POST["digits"] == "***")) $digits=$_POST["digits"]; 
echo "Courses you can take (with the correct tzmudim):<br>";
$data = json_decode(file_get_contents("courses_202002.json"), true);
if(!$data) echo "null!";
echo "<table><tr><th>Course Number</th><th>Course Name</th><th>Requirements</th><th>Tzmudim</th></tr>";

$classifications = "";
if(isset($_POST["chem"]) && $_POST["chem"] == "yes") $classifications .= " 123015 ";
if(isset($_POST["phys1"]) && $_POST["phys1"] == "yes") $classifications .= "113013 ";
if(isset($_POST["phys2"]) && $_POST["phys2"] == "yes") $classifications .= "113014";
foreach($data as $course) {
	if(substr($course["general"]["מספר מקצוע"], 0, 3) == $digits || $digits == "***") {
		if(isset($_POST["filter"]) && $_POST["filter"] == "yes" && !isset($course["general"]["מקצועות קדם"])) continue;
		if(!isset($course["general"]["מקצועות קדם"]) || check_kdamim($course["general"]["מקצועות קדם"], trim($_POST["courses"].$classifications), $course["general"]["שם מקצוע"])) { 
		        echo "<tr>";
			if(!isset($course["general"]["מקצועות קדם"])) $kdamim = "";
			else $kdamim = $course["general"]["מקצועות קדם"];
			if(!isset($course["general"]["מקצועות צמודים"])) $tzmudim = "";
			else $tzmudim = $course["general"]["מקצועות צמודים"];
			echo "<td>".$course["general"]["מספר מקצוע"] . "</td><td><p dir=\"rtl\">" . $course["general"]["שם מקצוע"] . "</p></td><td><p dir=\"rtl\">".$kdamim."</p></td><td><p dir=\"rtl\">".$tzmudim."</p></td>";
		        echo "</tr>";
		}
	}
}
echo "</table>";
?>
</body>
</html>
