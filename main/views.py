from django.http import HttpResponse
from django.shortcuts import render
import json
import time
# Create your views here.
from main import models

DEBUG = True

def parse_dependencies(course):
    required = list(map(lambda x: x.replace(" ", "").split("ו-"),
                        course["מקצועות קדם"].replace("\xa0", "").replace("(", "").replace(")", "").split(" או "))) if "מקצועות קדם" in course \
        else []
    adjacent = course["מקצועות צמודים"].split() if "מקצועות צמודים" in course else []
    contained = course["מקצועות זהים"].split() if "מקצועות זהים" in course else [] + \
        course["מקצועות ללא זיכוי נוסף"].split() if "מקצועות ללא זיכוי נוסף" in course else [] + \
        course["מקצועות ללא זיכוי נוסף (מוכלים)"].split() if "מקצועות ללא זיכוי נוסף (מוכלים)" in course else [] + \
        course["מקצועות ללא זיכוי נוסף (מכילים)"].split() if "מקצועות ללא זיכוי נוסף (מכילים)" in course else []
    return required, adjacent, contained


def create_courses_database(req):
    if not DEBUG:
        return HttpResponse("forbidden")

    f = open("courses_202101.json", encoding="utf8")
    j = json.load(f)
    count = 0
    t = time.time()
    for course in j:
        count += 1
        course = course['general']
        course_obj = models.Course(course_number=course["מספר מקצוע"], name=course["שם מקצוע"],
                                   points=float(course["נקודות"]))
        course_obj.save()
        (required, adjacent, contained) = parse_dependencies(course)
        for c2 in contained:
            if len(models.Contained.objects.filter(course1=course["מספר מקצוע"], course2=c2)) == 0:
                contained_obj = models.Contained(course1=course["מספר מקצוע"], course2=c2)
                contained_obj.save()
        for c2 in adjacent:
            adjacent_obj = models.Adjacent(requires=course["מספר מקצוע"], required=c2)
            adjacent_obj.save()
        for c2 in required:
            required_obj = models.Prerequisite(earlier_courses=str(c2), later_course=course["מספר מקצוע"])
            required_obj.save()

    return HttpResponse(f"Processed {count} courses in {time.time() - t} seconds")
