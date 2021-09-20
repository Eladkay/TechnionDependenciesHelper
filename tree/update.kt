var str = "var courses_unified = ["
str += getAllCourses().joinToString {
    "{\"course_id\":\"${it.number}\",\"course_name\":\"${it.name?.replace("\"", "\\\"")}\"," +
            "\"kdam\":\"${it.prerequisites?.replace(" ", "") ?: ""}\"," +
            "\"tsamud\":\"${it.adjacent?.replace(" ", "") ?: ""}\"}"
}
str += "]"
val f = File("courses_unified.js")
f.writeText(str)
// where getAllCourses() is https://gist.github.com/Eladkay/2d552b23942f6d199402be2fadbe8cfa