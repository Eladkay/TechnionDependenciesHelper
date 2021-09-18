from django.http import HttpResponse
from django.shortcuts import render
import json
import time

# Create your views here.
from django.views.decorators.csrf import csrf_exempt
from rest_framework import status
from rest_framework.decorators import api_view
from rest_framework.response import Response

from main import models


def parse_dependencies(course):
    required = list(map(lambda x: x.replace(" ", "").split("ו-"),
                        course["מקצועות קדם"].replace("\xa0", "").replace("(", "").replace(")", "").split(
                            " או "))) if "מקצועות קדם" in course else []
    adjacent = course["מקצועות צמודים"].split() if "מקצועות צמודים" in course else []
    contained = course["מקצועות זהים"].split() if "מקצועות זהים" in course else [] +\
    course["מקצועות ללא זיכוי נוסף"].split() if "מקצועות ללא זיכוי נוסף" in course else [] + \
    course["מקצועות ללא זיכוי נוסף (מוכלים)"].split() if "מקצועות ללא זיכוי נוסף (מוכלים)" in course else [] + \
    course["מקצועות ללא זיכוי נוסף (מכילים)"].split() if "מקצועות ללא זיכוי נוסף (מכילים)" in course else []
    return required, adjacent, contained


def create_courses_database(req):
    if len(models.Course.objects.all()):
        return HttpResponse("DB not empty!")

    f = open("courses_202101.json", encoding="utf8")
    j = json.load(f)
    count = 0
    t = time.time()
    for course in j:
        count += 1
        course = course['general']
        (required, adjacent, contained) = parse_dependencies(course)
        course_obj = models.Course(course_number=course["מספר מקצוע"], name=course["שם מקצוע"],
                                   points=float(course["נקודות"]), has_prerequisites=(len(required) > 0),
                                   has_adjacents=(len(adjacent) > 0), original_preqs=course['מקצועות קדם'] if "מקצועות קדם" in course else "",
                                   original_adjs=course['מקצועות צמודים'] if "מקצועות צמודים" in course else "")
        course_obj.save()
        for c2 in contained:
            contained_obj = models.Contained(course1=course["מספר מקצוע"], course2=c2)
            contained_obj.save()
        for c2 in adjacent:
            adjacent_obj = models.Adjacent(requires=course["מספר מקצוע"], required=c2)
            adjacent_obj.save()
        for option in required:
            required_obj = models.Prerequisite(later_course=course["מספר מקצוע"])
            required_obj.save()
            for c2 in option:
                course_preq = models.PrerequisiteSet(prerequisite_id=required_obj, earlier_course=c2)
                course_preq.save()

    return HttpResponse(f"Processed {count} courses in {time.time() - t} seconds")


@csrf_exempt
@api_view(['POST'])
def get_possible_courses(request):
    try:
        if "courses" not in request.data:
            return Response({"message": f"no courses were submitted, data is {request.data}"}, status=status.HTTP_400_BAD_REQUEST)
        courses = request.data["courses"]
        exclude_no_deps = False
        if "exclude_no_deps" in request.data:
            exclude_no_deps = request.data["exclude_no_deps"]
        exclude_contained = False
        if "exclude_contained" in request.data:
            exclude_contained = request.data["exclude_contained"]
        if "physics_mech" in request.data:
            courses += ["113013"]
        if "physics_elec" in request.data:
            courses += ["113014"]
        if "chem" in request.data:
            courses += ["123015"]
        filter = ""
        if "filter" in request.data:
            filter = request.data["filter"]
        ret = set()
        deps_set_used = []
        for course in models.Course.objects.all():
            if course.course_number in courses or filter not in course.course_number:
                continue
            if exclude_contained:
                flag = False
                contained1 = models.Contained.objects.all().filter(course1=course.course_number)
                contained2 = models.Contained.objects.all().filter(course2=course.course_number)
                for c in contained1:
                    if c.course2 in courses:
                        flag = True
                for c in contained2:
                    if c.course1 in courses:
                        flag = True
                if flag:
                    continue
            preqs = models.Prerequisite.objects.all().filter(later_course=course.course_number)
            if not preqs and not exclude_no_deps:
                adjs = list(map(lambda x: x.required, models.Adjacent.objects.all().filter(requires=course.course_number)))
                ret.add((course, adjs))
            for preq_set in preqs:
                courses_in_set = models.PrerequisiteSet.objects.all().filter(prerequisite_id=preq_set)
                course_numbers = map(lambda x: x.earlier_course, courses_in_set)
                flag = True
                for cn in course_numbers:
                    if cn not in courses:
                        flag = False
                if flag:
                    adjs = map(lambda x: x.required, models.Adjacent.objects.all().filter(requires=course.course_number))
                    ret.add((course, adjs))
        result = map(lambda x: {"name": x[0].name, "number": x[0].course_number, "pts": x[0].points,
                       "preqs": x[0].original_preqs, "adjs": x[1]}, ret)
        return Response(list(set(sorted(result, key=lambda x: x["number"]))), status=status.HTTP_200_OK)
    except Exception as e:
        return Response({"message": str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)


@csrf_exempt
@api_view(['POST'])
def get_dependent_courses(request):
    try:
        if "course" not in request.data:
            return Response({"message": "no course was submitted"}, status=status.HTTP_400_BAD_REQUEST)
        course = models.Course.objects.all().filter(course_number=request.data["course"])
        if not course:
            return Response({"message": "invalid course was submitted"}, status=status.HTTP_400_BAD_REQUEST)
        sets = models.PrerequisiteSet.objects.all().filter(earlier_course=request.data["course"])
        courses = map(lambda x: models.Course.objects.get(course_number=x.prerequisite_id.later_course), sets)
        return Response(map(
            lambda x: {"name": x.name, "number": x.course_number, "pts": x.points, "preqs": x.original_preqs,
                       "adjs": x.original_adjs}, courses), status=status.HTTP_200_OK)
    except Exception as e:
        return Response({"message": str(e)}, status=status.HTTP_500_INTERNAL_SERVER_ERROR)