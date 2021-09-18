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
        data = request.data
        if "_content" in data: # for debug
            data = json.loads(request.data["_content"])
            print(request.data["_content"])
        if "courses" not in data:
            return Response({"message": f"no courses were submitted, data is {data}"}, status=status.HTTP_400_BAD_REQUEST)
        courses = data["courses"]
        exclude_no_deps = False
        if "exclude_no_deps" in data:
            exclude_no_deps = data["exclude_no_deps"]
        exclude_contained = False
        if "exclude_contained" in data:
            exclude_contained = data["exclude_contained"]
        if "physics_mech" in data:
            courses += ["113013"]
        if "physics_elec" in data:
            courses += ["113014"]
        if "chem" in data:
            courses += ["123015"]
        filter = ""
        if "filter" in data:
            filter = data["filter"]
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
                ret.add(course)
            for preq_set in preqs:
                courses_in_set = models.PrerequisiteSet.objects.all().filter(prerequisite_id=preq_set)
                course_numbers = map(lambda x: x.earlier_course, courses_in_set)
                flag = True
                for cn in course_numbers:
                    if cn not in courses:
                        flag = False
                if flag:
                    ret.add(course)
        result = map(lambda x: {"name": x.name, "number": x.course_number, "pts": float(str(x.points)),
                       "preqs": x.original_preqs.replace(u'\xa0', u''), "adjs": x.original_adjs.replace(u'\xa0', u' ')}, ret)
        f = open("test.json", "w", encoding="utf8")
        v = sorted(result, key=lambda x: x["number"])
        f.write(str(v[0]))
        f.close()
        return Response(v, status=status.HTTP_200_OK)
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